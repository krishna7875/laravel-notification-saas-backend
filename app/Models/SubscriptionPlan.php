<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SubscriptionPlan represents a pricing tier offered to users.
 *
 * Each plan has monthly/yearly pricing, features, and status.
 */
class SubscriptionPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'features',
        'status',
    ];

    /**
     * Cast attributes to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'json',
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    /**
     * Get only active subscription plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
