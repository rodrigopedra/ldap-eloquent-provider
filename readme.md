# LDAP Auth for laravel

Based on a gist by rezen https://gist.github.com/rezen/ee5451eabea6e581256a

Added the ability to query the database to check if the user is authorized to use the app.

## Installation

```
composer require rodrigopedra/ldap-eloquent-auth
```

## Configuration

In your terminal/shell run

```
php artisan vendor:publish --provider="RodrigoPedra\LDAP\LDAPServiceProvider"
```

Then change this values in your files:

```php
// in your config/app.php add the provider to the service providers key

'providers' => [
    /* ... */
    
    'RodrigoPedra\LDAP\LDAPServiceProvider',
]
```

```php
// in your config/auth.php

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'ldap',
        ],
    ],

    'providers' => [
        'ldap' => [
            'driver' => 'ldap-auth',
            'model' => App\User::class,
        ],
    ],

```

```php
<?php

// in your config/ldap.php
return [
    'servers' => [
        'default' => [
            'server' => env( 'LDAP_SERVER' ),
            'domain' => env( 'LDAP_DOMAIN' ),
        ],
        'other' => [
            'server' => 'OTHER_SERVER',
            'domain' => 'OTHER_DOMAIN',
        ], 
        // ...
        // you can specify multiple servers, the driver will   
        // try to log the user in the order specified here
    ],
];
```

Also, add a username field to your user migration

```php
// create_user_table migration
$table->string('username')->unique();
```

### License

This package is open-sourced software licensed under the [MIT license](LICENSE.md)
