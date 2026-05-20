<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'capacity',
        'floor',
        'status',
        'qr_code'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
