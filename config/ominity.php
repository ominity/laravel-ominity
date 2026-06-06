<?php

/**
 * Copyright (c) 2024, Ominity (Connexeon BV)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @license     Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @author      Ominity <info@ominity.com>
 * @copyright   Ominity (Connexeon BV).
 *
 * @link        https://www.ominity.com
 */
return [

    'key' => env('OMINITY_KEY', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'),
    'endpoint' => env('OMINITY_API_ENDPOINT', 'https://api.ominity.com'),
    'localization' => env('OMINITY_LOCALIZATION', true),

    'pages' => [
        'cache' => [
            'enabled' => env('OMINITY_PAGES_CACHE_ENABLED', true),
            'expiration' => env('OMINITY_PAGES_CACHE_EXPIRATION', 3600), // Cache expiration time in seconds
            'store' => env('OMINITY_PAGES_CACHE_STORE', 'file'), // Cache store location
            'pre_render' => env('OMINITY_PAGES_CACHE_PRERENDER', true), // Enable pre-rendering of pages
        ],

        'components' => [],
    ],

    'menus' => [
        'cache' => [
            'enabled' => env('OMINITY_MENUS_CACHE_ENABLED', true),
            'expiration' => env('OMINITY_MENUS_CACHE_EXPIRATION', 3600), // Cache expiration time in seconds
            'store' => env('OMINITY_MENUS_CACHE_STORE', 'file'), // Cache store location
            'pre_render' => env('OMINITY_MENUS_CACHE_PRERENDER', true), // Enable pre-rendering of menus
        ],
    ],

    'forms' => [
        'cache' => [
            'enabled' => env('OMINITY_FORMS_CACHE_ENABLED', true),
            'expiration' => env('OMINITY_FORMS_CACHE_EXPIRATION', 3600), // Cache expiration time in seconds
            'store' => env('OMINITY_FORMS_CACHE_STORE', 'file'), // Cache store location
            'pre_render' => env('OMINITY_FORMS_CACHE_PRERENDER', true), // Enable pre-rendering of menus
        ],
        'recaptcha' => [
            'enabled' => env('OMINITY_FORMS_RECAPTCHA_ENABLED', false),
            'site_key' => env('OMINITY_FORMS_RECAPTCHA_SITE_KEY', ''),
            'secret_key' => env('OMINITY_FORMS_RECAPTCHA_SECRET_KEY', ''),
            'version' => env('OMINITY_FORMS_RECAPTCHA_VERSION', 'v2'),
            'score' => env('OMINITY_FORMS_RECAPTCHA_SCORE', 0.5), // Minimum score for reCAPTCHA v3
        ],
        'fields' => [
            'phone' => [
                'country_enabled' => env('OMINITY_FORMS_PHONE_COUNTRY_ENABLED', false), // Enable custom country list
                'default_country' => env('OMINITY_FORMS_PHONE_DEFAULT_COUNTRY', null), // Default country for phone fields
            ],
        ],
    ],

    'routes' => [
        'cache' => [
            'enabled' => env('OMINITY_ROUTES_CACHE_ENABLED', true),
            'expiration' => env('OMINITY_ROUTES_CACHE_EXPIRATION', 3600), // Cache expiration time in seconds
            'store' => env('OMINITY_ROUTES_CACHE_STORE', 'file'), // Cache store location
        ],
    ],

    'cart' => [
        'cookie_name' => env('OMINITY_CART_COOKIE_NAME', 'ominity_cart'),
        'cookie_expiration' => env('OMINITY_CART_COOKIE_EXPIRATION', 60 * 24 * 30), // 30 days
    ],

    'tracking' => [
        'enabled' => env('OMINITY_TRACKING_ENABLED'),
        'send_in_local' => env('OMINITY_TRACKING_SEND_IN_LOCAL', false),
        'log_in_local' => env('OMINITY_TRACKING_LOG_IN_LOCAL', true),
        'log_events' => env('OMINITY_TRACKING_LOG_EVENTS', false),
        'log_channel' => env('OMINITY_TRACKING_LOG_CHANNEL'),
        'route' => [
            'path' => env('OMINITY_TRACKING_ROUTE_PATH', '/ominity/tracking/events'),
        ],
        'cookie' => [
            'name' => env('OMINITY_TRACKING_COOKIE_NAME', '_omtvid'),
            'expiration' => env('OMINITY_TRACKING_COOKIE_EXPIRATION', 60 * 24 * 365), // minutes
            'path' => env('OMINITY_TRACKING_COOKIE_PATH', '/'),
            'same_site' => env('OMINITY_TRACKING_COOKIE_SAME_SITE', 'lax'),
            'secure' => env('OMINITY_TRACKING_COOKIE_SECURE'),
        ],
        'frontend' => [
            'sample_rate' => env('OMINITY_TRACKING_SAMPLE_RATE', 1),
            'track_page_views' => env('OMINITY_TRACKING_PAGE_VIEWS', true),
            'track_sessions' => env('OMINITY_TRACKING_SESSIONS', true),
            'track_scroll_depth' => env('OMINITY_TRACKING_SCROLL_DEPTH', true),
            'scroll_depth_thresholds' => [25, 50, 75, 100],
            'track_outbound_clicks' => env('OMINITY_TRACKING_OUTBOUND_CLICKS', true),
            'track_file_downloads' => env('OMINITY_TRACKING_FILE_DOWNLOADS', true),
            'track_form_submissions' => env('OMINITY_TRACKING_FORM_SUBMISSIONS', true),
            'track_custom_clicks' => env('OMINITY_TRACKING_CUSTOM_CLICKS', true),
            'flush_queue_on_mount' => env('OMINITY_TRACKING_FLUSH_QUEUE', true),
            'queue_key' => env('OMINITY_TRACKING_QUEUE_KEY', '__ominity_tracking_queue_v1'),
            'session_key' => env('OMINITY_TRACKING_SESSION_KEY', '__ominity_tracking_session_v1'),
            'max_queue_size' => env('OMINITY_TRACKING_MAX_QUEUE_SIZE', 50),
            'event_names' => [],
            'extra_metadata' => [],
        ],
    ],

    'users' => [
        'mfa' => [
            'enabled' => env('OMINITY_MFA_ENABLED', false),
            'methods' => [
                'email',
                'sms',
                'authenticator',
            ],
        ],
    ],

    'svg' => [
        'cache' => [
            'enabled' => env('OMINITY_SVG_CACHE_ENABLED', true),
            'expiration' => env('OMINITY_SVG_CACHE_EXPIRATION', 2592000), // 30 days
            'store' => env('OMINITY_SVG_CACHE_STORE', 'file'),
        ],
    ],

    // If you intend on using Ominity User Provider, place the following in the 'config/auth.php'
    // 'guards' => [
    //      // Other guards...
    //
    //      'ominity' => [
    //          'driver' => 'session',
    //          'provider' => 'ominity_users',
    //      ],
    // ],
    // 'providers' => [
    //      // Other providers...
    //
    //      'ominity_users' => [
    //          'driver' => 'ominity',
    //          'client_id' => env('OMINITY_CLIENT_ID'),
    //          'client_secret' => env('OMINITY_CLIENT_SECRET'),
    //      ]
    // ],

    // If you intend on using Ominity OAauth, place the following in the 'config/services.php'
    // 'ominity' => [
    //     'client_id'     => env('OMINITY_CLIENT_ID'),
    //     'client_secret' => env('OMINITY_CLIENT_SECRET'),
    //     'redirect'      => env('OMINITY_REDIRECT_URI'),
    // ],
];
