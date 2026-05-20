<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_id',
        'type',
        'total',
        'tax',
        'discount',
        'status',
        'payment_method',
        'payment_status',
        'paid_amount',
        'change_amount',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'total' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(CafeTable::class, 'table_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Check if order is paid
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    // Mark order as paid
    public function markAsPaid(float $paidAmount, ?float $changeAmount = null): void
    {
        $this->update([
            'payment_status' => 'paid',
            'paid_amount' => $paidAmount,
            'change_amount' => $changeAmount,
            'paid_at' => now(),
        ]);
    }
}
