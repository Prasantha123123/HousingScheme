<?php
// App/Models/House.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * House model with authentication capabilities.
 * 
 * Authenticates directly against the houses table when HouseOwneId is NULL.
 * Primary key is houseNo (string, non-incrementing).
 * Password field is house_password (bcrypt hash).
 */
class House extends Authenticatable
{
    protected $table = 'houses';
    protected $primaryKey = 'houseNo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['houseNo', 'HouseOwneId', 'house_password'];

    // Hide password from serialization
    protected $hidden = ['house_password'];

    /**
     * Get the password for authentication.
     * Required by Authenticatable interface.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->house_password;
    }

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
