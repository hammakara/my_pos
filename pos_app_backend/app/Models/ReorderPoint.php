<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReorderPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id', 'minimum_quantity', 'reorder_quantity',
        'lead_time_days', 'preferred_supplier_id',
    ];

    public function productVariant()     { return $this->belongsTo(ProductVariant::class); }
    public function preferredSupplier()  { return $this->belongsTo(Supplier::class, 'preferred_supplier_id'); }
}
