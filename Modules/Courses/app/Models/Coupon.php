<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Schema;

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

    protected $appends = ['round_ids'];

    protected $casts = [
        'expiry_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }

    public function rounds()
    {
        return $this->belongsToMany(Rounds::class, 'coupon_round', 'coupon_id', 'round_id');
    }

    public function getRoundIdsAttribute(): array
    {
        if ($this->relationLoaded('rounds')) {
            $ids = $this->rounds->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            if ($ids) {
                return $ids;
            }
        } elseif (Schema::hasTable('coupon_round')) {
            $ids = $this->rounds()->pluck('rounds.id')->map(fn ($id) => (int) $id)->values()->all();
            if ($ids) {
                return $ids;
            }
        }

        return $this->round_id ? [(int) $this->round_id] : [];
    }

    public function appliesToRound($roundId): bool
    {
        if ($this->target !== 'rounds') {
            return false;
        }

        $ids = $this->round_ids;
        if (empty($ids)) {
            return true;
        }

        return in_array((int) $roundId, $ids, true);
    }

    public static function findForRound(?string $code, $roundId): ?self
    {
        if (!$code) {
            return null;
        }

        $query = static::where('code', strtoupper($code))->where('target', 'rounds');

        if (Schema::hasTable('coupon_round')) {
            $specific = (clone $query)->whereHas('rounds', function ($q) use ($roundId) {
                $q->where('rounds.id', $roundId);
            })->first();
            if ($specific) {
                return $specific;
            }
        }

        if (Schema::hasColumn('coupons', 'round_id')) {
            $specific = (clone $query)->where('round_id', $roundId)->first();
            if ($specific) {
                return $specific;
            }

            $global = (clone $query)->whereNull('round_id');
            if (Schema::hasTable('coupon_round')) {
                $global->whereDoesntHave('rounds');
            }

            return $global->first();
        }

        return $query->first();
    }

    public function isValidFor($target, $roundId = null)
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->target !== $target) {
            return false;
        }

        if ($target === 'rounds' && !$this->appliesToRound($roundId)) {
            return false;
        }

        return true;
    }

    public function getDiscountAmount($price)
    {
        if ($this->type === 'percentage') {
            return ($this->value / 100) * $price;
        }
        return $this->value;
    }
}
