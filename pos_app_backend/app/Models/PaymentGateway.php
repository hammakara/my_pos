<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'gateway_type',
        'api_key',
        'api_secret',
        'webhook_secret',
        'is_active',
        'is_test_mode',
        'configuration',
        'provider',
        'environment',
        'currency',
        'status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_test_mode' => 'boolean',
        'configuration' => 'array',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected $appends = [
        'provider',
        'environment',
        'currency',
        'status'
    ];

    public function getProviderAttribute()
    {
        return $this->gateway_type;
    }

    public function setProviderAttribute($value)
    {
        $this->attributes['gateway_type'] = $value;
    }

    public function getEnvironmentAttribute()
    {
        return $this->is_test_mode ? 'sandbox' : 'production';
    }

    public function setEnvironmentAttribute($value)
    {
        $this->attributes['is_test_mode'] = ($value === 'sandbox');
    }

    public function getCurrencyAttribute()
    {
        $config = $this->configuration ?? [];
        return $config['currency'] ?? 'USD';
    }

    public function setCurrencyAttribute($value)
    {
        $config = $this->configuration ?? [];
        $config['currency'] = $value;
        $this->attributes['configuration'] = json_encode($config);
    }

    public function getStatusAttribute()
    {
        return $this->is_active ? 'active' : 'inactive';
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['is_active'] = ($value === 'active');
    }
}