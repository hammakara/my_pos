<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'tax_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function productBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function reorderPoints(): HasMany
    {
        return $this->hasMany(ReorderPoint::class, 'preferred_supplier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
