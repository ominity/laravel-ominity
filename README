<h1 align="center">Ominity for Laravel</h1>

[![Latest Stable Version](https://poser.pugx.org/ominity/laravel-ominity/v/stable)](https://packagist.org/packages/ominity/laravel-ominity)
[![Total Downloads](https://poser.pugx.org/ominity/laravel-ominity/downloads)](https://packagist.org/packages/ominity/laravel-ominity)
[![License](http://poser.pugx.org/ominity/laravel-ominity/license)](https://packagist.org/packages/ominity/laravel-ominity)

## **Requirements**

* An active installation of [Ominity](https://www.ominity.com).
* Up-to-date OpenSSL (or other SSL/TLS toolkit)
* PHP >= 8.1
* [Laravel](https://www.laravel.com) >= 10.0

## Installation

You can install the package via Composer. Run the following command in your terminal:

```bash
composer require ominity/laravel-ominity
```

After installing the package, the Ominity service will be available for use in your Laravel application.

## Configuration

Publish the configuration file using the following Artisan command:

```bash
php artisan vendor:publish --provider="Ominity\Laravel\OminityServiceProvider"
```

This will create a `config/ominity.php` file where you can configure the package settings.

## Tracking

The package now includes a first-party visitor/event tracking layer.

Add the existing script directive to your layout:

```blade
@ominity_scripts
```

That now does two things:

1. loads `vendor/ominity/ominity.js`
2. boots the tracking runtime with the current visitor ID, authenticated user ID, route endpoint, and page metadata

Tracked automatically in the browser:

* page views
* session starts
* scroll depth milestones
* outbound link clicks
* file downloads
* native form submits
* custom click events via `data-ominity-event`

Configuration lives under `config/ominity.php` in the `tracking` section.

Important environment flags:

```env
OMINITY_TRACKING_ENABLED=true
OMINITY_TRACKING_SEND_IN_LOCAL=false
OMINITY_TRACKING_LOG_IN_LOCAL=true
```

Local environment behavior defaults to logging events instead of forwarding them to Ominity.

For manual page metadata you can use:

```blade
@ominity_tracking_meta([
    'origin_resource' => [
        'resource' => 'product',
        'id' => $product->id,
        'slug' => $product->slug,
    ],
])
```

For server-side access:

```php
Ominity::tracking()->track([
    'event' => 'purchase',
    'visitorId' => Ominity::tracking()->getVisitorId(),
    'metadata' => [
        'order_id' => $order->id,
    ],
]);
```

## License

[The MIT License](LICENSE.md). Copyright (c) 2024, Ominity (Connexeon BV)
