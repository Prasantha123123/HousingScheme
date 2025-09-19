<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Shop model for admin management and authentication.
 * 
 * Stores shop information with plain text passwords for admin visibility.
 * Primary key is shopNumber (string, non-incrementing).
 */
class Shop extends Model implements Authenticatable
{
    protected $table = 'Shops';
    protected $primaryKey = 'shopNumber';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'shopNumber', 'MerchantId', 'leaseEnd', 'rentalAmount', 'shop_password','timestamp'
    ];

    /**
     * Get the column name for the primary key.
     * Override to ensure Laravel uses the correct identifier field.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'shopNumber';
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
        return $this->shop_password;
    }

    /**
     * Get the password column name.
     *
     * @return string
     */
    public function getAuthPasswordName()
    {
        return 'shop_password';
    }

    /**
     * Get the remember token.
     */
    public function getRememberToken()
    {
        return null; // Not implemented for shops
    }

    /**
     * Set the remember token.
     */
    public function setRememberToken($value)
    {
        // Not implemented for shops
    }

    /**
     * Get the remember token name.
     */
    public function getRememberTokenName()
    {
        return null; // Not implemented
    }

    /**
     * Relationship: Shop belongs to a User (merchant).
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'MerchantId'); // users.id
    }

    /**
     * Check if shop is available for direct login.
     * Only unassigned shops (MerchantId is NULL) can authenticate directly.
     *
     * @return bool
     */
    public function canLoginDirectly()
    {
        return is_null($this->MerchantId) && !empty($this->shop_password);
    }
}
