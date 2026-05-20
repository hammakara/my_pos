<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_number',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'tax_id',
        'customer_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
        'customer_type' => 'string',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function credit(): HasOne
    {
        return $this->hasOne(CustomerCredit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBusiness($query)
    {
        return $query->where('customer_type', 'business');
    }

    public function scopeIndividual($query)
    {
        return $query->where('customer_type', 'individual');
    }
}
