<?php

namespace Ominity\Laravel\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Ominity\Api\OminityApiClient;
use Ominity\Api\Resources\Commerce\Cart;
use Ominity\Api\Types\CartStatus;
use Ominity\Api\Types\CartType;
use Ominity\Laravel\Models\User;

class OminityCartService
{
    protected OminityApiClient $ominity;

    protected array $config;

    protected ?Cart $cart = null;

    public function __construct(OminityApiClient $ominity, array $config)
    {
        $this->ominity = $ominity;
        $this->config = $config;
    }

    /**
     * Get the cart
     *
     * @param  string  $country
     * @param  string|null  $currency
     * @param  string|null  $type
     * @return Cart|null
     */
    public function get($country, $currency = null, $type = null)
    {
        $type = $this->getType($type);

        if ($this->cart &&
            $this->cart->type == $type &&
            $this->cart->country == $country &&
            ($currency === null || $this->cart->currency == $currency)) {
            return $this->cart;
        }

        if ($type == CartType::GUEST) {
            return $this->getGuestCart($country, $currency);
        }

        if ($type == CartType::PERSONAL || $type == CartType::WISHLIST) {
            $carts = $this->ominity->commerce->carts->page(1, 1, [
                'include' => ['items', 'items.product', 'shippingMethod'],
                'sort' => '-createdAt',
                'filter' => [
                    'user' => Auth::id(),
                    'type' => $type,
                    'status' => CartStatus::PENDING,
                ],
            ]);

            if ($carts->count() > 0) {
                $this->cart = $carts->first();

                return $this->cart;
            }

            if ($type == CartType::PERSONAL) {
                $guestCart = $this->getGuestCart($country, $currency);

                if ($guestCart) {
                    $this->cart = $guestCart;
                    $this->cart->userId = Auth::id();
                    $this->cart->update();

                    return $this->cart;
                }
            }
        }

        if ($type == CartType::SHARED) {
            /** @var \Ominity\Laravel\Models\User $user */
            $user = Auth::user();
            $customerUser = $user->getCurrentCustomer();

            $carts = $this->ominity->commerce->carts->page(1, 1, [
                'include' => ['items', 'items.product', 'shippingMethod'],
                'sort' => '-createdAt',
                'filter' => [
                    'customer' => $customerUser->customerId,
                    'type' => $type,
                    'status' => CartStatus::PENDING,
                ],
            ]);

            if ($carts->count() > 0) {
                $cart = $carts->first();

                $this->cart = $cart;

                return $this->cart;
            }
        }

        return null;
    }

    /**
     * Create a new cart
     *
     * @param  string  $country
     * @param  string|null  $currency
     * @param  string|null  $type
     * @return Cart
     */
    public function create($country, $currency = null, $type = null)
    {
        $type = $this->getType($type);

        $body = [
            'type' => $type,
            'country' => $country,
        ];

        if ($currency) {
            $body['currency'] = $currency;
        }

        if ($type != CartType::GUEST) {
            $body['userId'] = Auth::id();
        }

        if ($type == CartType::SHARED) {
            /** @var \Ominity\Laravel\Models\User $user */
            $user = Auth::user();
            $customerUser = $user->getCurrentCustomer();

            $body['customerId'] = $customerUser->customerId;
        }

        $cart = $this->ominity->commerce->carts->create($body);

        $this->cart = $cart;

        if ($cart->isGuestCart()) {
            $this->setCartId($cart->id);
        }

        return $this->cart;
    }

    /**
     * Add an item to the cart
     *
     * @param  int  $productId
     * @param  int|null  $productOfferId
     * @param  int  $quantity
     * @return Cart|null
     */
    public function addItem($productId, $productOfferId = null, $quantity = 1)
    {
        if (! $this->cart) {
            return null;
        }

        $this->cart->addItem([
            'productId' => $productId,
            'productOfferId' => $productOfferId,
            'quantity' => $quantity,
        ]);

        $this->cart = $this->cart->update();

        return $this->cart;
    }

    /**
     * Remove an item from the cart
     *
     * @param  string  $itemId
     * @return Cart|null
     */
    public function removeItem($itemId)
    {
        if (! $this->cart) {
            return null;
        }

        $this->cart->removeItem($itemId);

        $this->cart = $this->cart->update();

        return $this->cart;
    }

    /**
     * Apply a promotion code to the cart
     *
     * @param  string  $promotionCode
     * @return Cart|null
     * @throws \Ominity\Laravel\Exceptions\InvalidPromotionCodeException
     */
    public function applyPromotionCode($promotionCode)
    {
        if (! $this->cart) {
            return null;
        }

        $this->cart->promotionCodes = array_merge($this->cart->promotionCodes ?? [], [$promotionCode]);

        $this->cart = $this->cart->update();

        if(! in_array($promotionCode, $this->cart->promotionCodes ?? [])) {
            throw new \Ominity\Laravel\Exceptions\InvalidPromotionCodeException();
        }

        return $this->cart;
    }

    /**
     * Remove a promotion code from the cart
     *
     * @param  string|null  $promotionCode
     * @return Cart|null
     */
    public function removePromotionCode($promotionCode = null)
    {
        if (! $this->cart) {
            return null;
        }

        if ($promotionCode) {
            $this->cart->promotionCodes = array_filter($this->cart->promotionCodes ?? [], function ($c) use ($promotionCode) {
                return $c != $promotionCode;
            });
        } else {
            $this->cart->promotionCodes = [];
        }

        $this->cart = $this->cart->update();

        return $this->cart;
    }

    public function update()
    {
        if (! $this->cart) {
            return null;
        }

        $this->cart = $this->cart->update();

        return $this->cart;
    }

    public function unset()
    {
        $this->cart = null;
        $this->setCartId(null);
    }

    /**
     * Validate the cart ownership
     *
     * @return bool
     */
    protected function validate(Cart $cart)
    {
        if ($cart->isCompleted()) {
            return false;
        }

        if ($cart->isGuestCart()) {
            if (! Auth::check()) {
                return true;
            }
        }

        if ($cart->isPersonalCart() || $cart->isWishlistCart()) {
            if (Auth::check() && $cart->userId == Auth::id()) {
                return true;
            }
        }

        if ($cart->isSharedCart()) {
            if (Auth::check() && Auth::user() instanceof User) {
                /** @var \Ominity\Laravel\Models\User $user */
                $user = Auth::user();
                $customerUser = $user->getCurrentCustomer();

                if ($cart->customerId == $customerUser->customerId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the cart id
     *
     * @return string|null
     */
    protected function getCartId()
    {
        return Session::get('ominity_cart') ?? Cookie::get($this->config['cookie_name']);
    }

    /**
     * Set the cart id
     *
     * @param  string  $cartId
     */
    protected function setCartId($cartId)
    {
        Cookie::queue($this->config['cookie_name'], $cartId, $this->config['cookie_expiration']);
        Session::put('ominity_cart', $cartId);
        Session::save();
    }

    /**
     * Get the cart type based on the user
     *
     * @param  string|null  $type
     * @return string
     */
    protected function getType($type = null)
    {
        if (! Auth::check()) {
            return CartType::GUEST;
        }

        if (empty($type)) {
            return CartType::PERSONAL;
        }

        if ($type == CartType::WISHLIST) {
            return CartType::WISHLIST;
        }

        if ($type == CartType::SHARED) {
            if (Auth::user() instanceof User) {
                /** @var \Ominity\Laravel\Models\User $user */
                $user = Auth::user();
                $customerUser = $user->getCurrentCustomer();

                if ($customerUser) {
                    return CartType::SHARED;
                }
            }
        }

        return CartType::PERSONAL;
    }

    /**
     * Get the guest cart
     *
     * @param  string|null  $country
     * @param  string|null  $currency
     * @return Cart|null
     */
    protected function getGuestCart($country = null, $currency = null)
    {
        $cartId = $this->getCartId();

        if ($cartId) {
            try {
                $cart = $this->ominity->commerce->carts->get($cartId, [
                    'include' => ['items', 'items.product', 'shippingMethod'],
                    'country' => $country ?? '',
                    'currency' => $currency ?? '',
                ]);

                if (! $cart->isPending() || ! $cart->isGuestCart()) {
                    $this->unset();

                    return null;
                }

                $this->cart = $cart;
                $this->setCartId($cart->id);

                return $this->cart;
            } catch (\Exception $e) {
                $this->unset();

                return null;
            }
        }

        return null;
    }
}
