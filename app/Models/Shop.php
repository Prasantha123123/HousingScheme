<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shop model with authentication capabilities.
 * 
 * Authenticates directly against the Shops table when MerchantId is NULL.
 * Primary key is shopNumber (string, non-incrementing).
 * Password field is shop_password (bcrypt hash).
 */
class Shop extends Authenticatable
{
    protected $table = 'Shops';                 // from your migration
    protected $primaryKey = 'shopNumber';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'shopNumber', 'MerchantId', 'leaseEnd', 'rentalAmount', 'shop_password','timestamp'
    ];

    // Hide password from serialization
    protected $hidden = ['shop_password'];

    /**
     * Get the password for authentication.
     * Required by Authenticatable interface.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->shop_password;
    }

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
