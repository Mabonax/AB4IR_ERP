<?php

return [
    'platform_name' => env('APP_NAME', 'Programme of Action ERP'),
    'short_name' => env('BRAND_SHORT_NAME', 'POA ERP'),
    'tagline' => env(
        'BRAND_TAGLINE',
        'Organisation governance, compliance, delivery, and reporting platform.'
    ),
    'logo_path' => env('BRAND_LOGO_PATH', 'logo.png'),
    'support_email' => env('BRAND_SUPPORT_EMAIL', env('MAIL_FROM_ADDRESS', 'support@example.com')),
    'pdf_footer' => env(
        'BRAND_PDF_FOOTER',
        'Programme of Action ERP | Organisation governance, compliance, delivery, and reporting'
    ),
];
