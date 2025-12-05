<?php

use Illuminate\Http\Request;

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Define which proxies are trusted to pass X-Forwarded headers to this app.
    | You may trust every proxy, none at all, or a chosen list of addresses.
    | Use TRUSTED_PROXIES for '*', nothing, or a list of proxy addresses.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', ''),

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxy Headers
    |--------------------------------------------------------------------------
    |
    | Specify which forwarded headers should be trusted when resolving requests.
    | Common choices include X-Forwarded-For, Host, Port, Proto, and AWS ELB.
    | Change this bitmask if proxies send fewer or nonstandard headers too
    |
    */

    'headers' => Request::HEADER_X_FORWARDED_FOR
        | Request::HEADER_X_FORWARDED_HOST
        | Request::HEADER_X_FORWARDED_PORT
        | Request::HEADER_X_FORWARDED_PROTO
        | Request::HEADER_X_FORWARDED_AWS_ELB,

];
