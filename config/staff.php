<?php

return [
    'public_registration' => env('PUBLIC_REGISTRATION_ENABLED', false),
    'default_password' => env('STAFF_USER_DEFAULT_PASSWORD', 'password'),
    'send_welcome_notification' => env('STAFF_SEND_WELCOME_NOTIFICATION', false),
];
