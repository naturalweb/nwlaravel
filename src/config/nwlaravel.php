<?php

return [
    /**
     * Use helpers "asDateTime" e "fromDateTime"
     * @see src/NwLaravel/helpers.php
     */
    'date_format' => 'd/m/Y',

    /**
     * Use to proxyUrl
     * @see src/NwLaravel/OAuth/OAuthProxy.php
     */
    'oauth' => [
        'urlToken' => '/oauth/access-token',
    ]
];
