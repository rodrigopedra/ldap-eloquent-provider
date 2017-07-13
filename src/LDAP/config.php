<?php

return [
    'servers' => [
        'default' => [
            'server' => env( 'LDAP_SERVER' ),
            'domain' => env( 'LDAP_DOMAIN' ),
        ],
    ],
];
