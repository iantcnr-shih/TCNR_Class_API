<?php

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'captcha/*',        // ✅ 允許所有 captcha 路徑
        'my-captcha/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://10.140.241.130:3000',
        'http://127.0.0.1:3000',
        'http://localhost:3000',
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];