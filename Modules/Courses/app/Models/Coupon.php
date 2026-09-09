<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'target',
        'round_id',
        'usage_limit',
        'used_count',
        'expiry_date',
        'is_active'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    /**
     * Check if coupon is valid.
     */
    public function isValidFor($target, $roundId = null)
    {
        if (!$this->is_active) return false;
        
        if ($this->expiry_date && $this->expiry_date->isPast()) return false;

        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) return false;

        if ($this->target !== $target) return false;

        if ($target === 'rounds' && $this->round_id && $this->round_id != $roundId) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount.
     */
    public function getDiscountAmount($price)
    {
        if ($this->type === 'percentage') {
            return ($this->value / 100) * $price;
        }
        return $this->value;
    }
}
