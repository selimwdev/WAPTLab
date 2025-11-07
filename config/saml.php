<?php
return [
    'strict' => true,
    'debug' => env('APP_DEBUG', false),

    'sp' => [
        'entityId' => env('SAML_SP_ENTITYID', 'https://your-app.example.com/metadata'),
        'assertionConsumerService' => [
            'url' => env('SAML_ACS_URL', 'https://your-app.example.com/saml/acs'),
        ],
        'singleLogoutService' => [
            'url' => env('SAML_SLS_URL', 'https://your-app.example.com/saml/sls'),
        ],
        'x509cert' => trim(file_get_contents(storage_path('saml/saml-public.crt'))),
        'privateKey' => trim(file_get_contents(storage_path('saml/saml-private.pem'))),
    ],

    'idp' => [
        // بعد ما تعمل إعداد الـ IdP (Okta/Keycloak) هتملأ القيم دي
        'entityId' => env('SAML_IDP_ENTITYID', 'https://idp.example.com/metadata'),
        'singleSignOnService' => [
            'url' => env('SAML_IDP_SSO', 'https://idp.example.com/sso'),
        ],
        'singleLogoutService' => [
            'url' => env('SAML_IDP_SLO', 'https://idp.example.com/slo'),
        ],
        'x509cert' => env('SAML_IDP_CERT', ''), // cert من IdP (base64 PEM)
    ],
];
