<?php

namespace RodrigoPedra\LDAP;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Class LDAPUserProvider
 *
 * based on a gist by github's user rezen
 * @see https://gist.github.com/rezen/ee5451eabea6e581256a
 *
 * @package RodrigoPedra\LDAP
 */
class LDAPUserProvider implements UserProvider
{
    /**
     * The Eloquent user model class name.
     *
     * @var string
     */
    protected $model;

    /**
     * Create a new database user provider.
     *
     * @param  string $model
     */
    public function __construct( $model )
    {
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById( $identifier )
    {
        return $this->createModel()->newQuery()->find( $identifier );
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string $token
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken( $identifier, $token )
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where( $model->getKeyName(), $identifier )
            ->where( $model->getRememberTokenName(), $token )
            ->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string                                     $token
     *
     * @return void
     */
    public function updateRememberToken( UserContract $user, $token )
    {
        $user->setRememberToken( $token );

        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials( array $credentials )
    {
        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (!str_contains( $key, 'password' )) {
                $query->where( $key, $value );
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array                                      $credentials
     *
     * @return bool
     */
    public function validateCredentials( UserContract $user, array $credentials )
    {
        return $user->exists && $this->validateWithLDAP( $credentials );
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\' . ltrim( $this->model, '\\' );

        return new $class;
    }

    /**
     * Connects to LDAP and validates the credentials
     *
     * @param  array $credentials - passes in username / password
     *
     * @return bool
     * @throws LDAPException
     */
    protected function validateWithLDAP( array $credentials )
    {
        $server = config('ldap.server', false);

        if (empty($server)) {
            throw new LDAPException('LDAP server missing');
        }

        $ldap   = ldap_connect( $server );

        $domain = config( 'ldap.domain', false );
        $domain = ( $domain ) ? "{$domain}\\" : '';

        /**
         * If the username and password is not @ least 3 chars ...
         * Prevents ldap_connect with password = abc
         */
        if (strlen( $credentials[ 'username' ] ) < 3 || strlen( $credentials[ 'password' ] ) < 3) {
            return false;
        }

        // If connection succeeds, then user is valid
        // ldap_bind fails hard with invalid credentials so let's silence it with @
        try {
            $ldap_bind = @ldap_bind( $ldap, $domain . $credentials[ 'username' ], $credentials[ 'password' ] );

            if (!$ldap_bind) {
                return false;
            }

            return true;
        } catch ( \Exception $e ) {

            // otherwise invalid
            return false;
        }
    }
}
