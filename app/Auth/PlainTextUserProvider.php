<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Arr;

class PlainTextUserProvider extends EloquentUserProvider implements UserProvider
{
    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherContract $hasher, $model)
    {
        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
           (count($credentials) === 1 &&
            array_key_exists('password', $credentials))) {
            return;
        }

        // Build the query for the model
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if ($key === 'password') {
                continue;
            }

            if (is_array($value) || $value instanceof \UnitEnum) {
                $query->whereIn($key, Arr::wrap($value));
            } elseif ($value === null) {
                $query->whereNull($key);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // Get the password from credentials (Laravel always passes it as 'password')
        $password = $credentials['password'] ?? null;
        
        if ($password === null) {
            return false;
        }
        
        $userPassword = $user->getAuthPassword();
        
        // Check if the stored password is a bcrypt hash (starts with $2y$)
        $isPasswordHashed = str_starts_with($userPassword, '$2y$');
        
        $isValid = false;
        if ($isPasswordHashed) {
            // Use bcrypt verification for hashed passwords
            $isValid = $this->hasher->check($password, $userPassword);
        } else {
            // Use direct comparison for plain text passwords
            $isValid = $password === $userPassword;
        }
        
        \Log::info('PlainTextUserProvider credential validation', [
            'user_class' => get_class($user),
            'user_identifier' => $user->getAuthIdentifier(),
            'provided_password_length' => strlen($password),
            'stored_password_length' => strlen($userPassword ?? ''),
            'is_password_hashed' => $isPasswordHashed ? 'yes' : 'no',
            'validation_method' => $isPasswordHashed ? 'bcrypt' : 'plain_text',
            'passwords_match' => $isValid ? 'yes' : 'no'
        ]);
        
        return $isValid;
    }
}