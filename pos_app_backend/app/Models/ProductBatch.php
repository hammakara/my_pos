<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id', 'batch_number', 'quantity', 'remaining_quantity',
        'cost_price', 'manufacturing_date', 'expiry_date',
        'supplier_id', 'purchase_order_id', 'status',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date'        => 'date',
        'cost_price'         => 'decimal:2',
    ];

    public function productVariant() { return $this->belongsTo(ProductVariant::class); }
    public function supplier()       { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder()  { return $this->belongsTo(PurchaseOrder::class); }
}
