<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_id',
        'amount',
        'tip_type',
        'employee_id',
        'distribution_status',
        'distributed_at',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'distributed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function distributions()
    {
        return $this->hasMany(TipDistribution::class);
    }
}