<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Cryptographic Secret Key
    |--------------------------------------------------------------------------
    |
    | High-entropy key used to sign and verify HMAC-SHA256 signatures.
    | Cannot be guessed or forged by unauthorized actors.
    |
    */
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Signature Algorithm
    |--------------------------------------------------------------------------
    */
    'algorithm' => 'HS256',

    /*
    |--------------------------------------------------------------------------
    | Short-Lived Access Token TTL
    |--------------------------------------------------------------------------
    |
    | Access tokens expire every few minutes (default: 15 minutes) to prevent
    | unauthorized replay even in the event of client-side token interception.
    |
    */
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | Rotating Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Refresh token duration in minutes (default: 10,080 min = 7 days).
    | Rotates upon every refresh call (single-use semantics).
    |
    */
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 10080),

    /*
    |--------------------------------------------------------------------------
    | Token Issuer & Audience Claims (RFC 7519)
    |--------------------------------------------------------------------------
    */
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost:8000')),
    'audience' => env('JWT_AUDIENCE', 'school-news-api'),

    /*
    |--------------------------------------------------------------------------
    | Opaque Payload Encryption (Anti-Decoder Protection)
    |--------------------------------------------------------------------------
    |
    | When enabled, all user claims and sensitive identifiers are encrypted
    | with AES-256 before token signing. Any public decoder (such as jwt.io)
    | will only see indecipherable ciphertext, preventing information leakage.
    |
    */
    'encrypt_payload' => true,
];
