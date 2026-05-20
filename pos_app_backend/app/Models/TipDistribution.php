<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'tip_id',
        'employee_id',
        'amount',
        'percentage',
        'distribution_date',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'distribution_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function tip()
    {
        return $this->belongsTo(Tip::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}