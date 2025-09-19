<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * House model for admin management and authentication.
 * 
 * Stores house information with plain text passwords for admin visibility.
 * Primary key is houseNo (string, non-incrementing).
 */
class House extends Model implements Authenticatable
{
    protected $table = 'houses';
    protected $primaryKey = 'houseNo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['houseNo', 'HouseOwneId', 'house_password'];

    /**
     * Get the column name for the primary key.
     * Override to ensure Laravel uses the correct identifier field.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'houseNo';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    /**
     * Get the password for authentication.
     * Since we store plain text, return it as-is.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->house_password;
    }

    /**
     * Get the password column name.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'house_password';
    }

    /**
     * Get the remember token.
     */
    public function getRememberToken()
    {
        return null; // Not implemented for houses
    }

    /**
     * Set the remember token.
     */
    public function setRememberToken($value)
    {
        // Not implemented for houses
    }

    /**
     * Get the remember token name.
     */
    public function getRememberTokenName()
    {
        return null; // Not implemented
    }

    /**
     * Relationship: House belongs to a User (owner).
     */
    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'HouseOwneId');
    }

    /**
     * Check if house is available for direct login.
     * Only unassigned houses (HouseOwneId is NULL) can authenticate directly.
     *
     * @return bool
     */
    public function canLoginDirectly()
    {
        return is_null($this->HouseOwneId) && !empty($this->house_password);
    }
}
