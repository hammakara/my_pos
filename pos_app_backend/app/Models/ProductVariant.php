<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_name',
        'price',
        'stock_qty',
        'barcode'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
