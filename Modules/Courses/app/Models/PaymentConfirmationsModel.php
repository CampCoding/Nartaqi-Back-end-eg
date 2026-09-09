<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentConfirmationsModel extends Model
{
    use HasFactory;

    protected $table = 'payment_confirmations';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'round_id',
        'phone',
        'amount',
        'sender_name',
        'receiver_bank',
        'status',
        'image',
        'created_at',
        'student_id'
    ];

    public $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url('storage/' . $this->image);
        }
        return null;
    }
    // Relationship to the round this confirmation belongs to
    public function round()
    {
        return $this->belongsTo(\Modules\Courses\Models\Rounds::class, 'round_id');
    }

    // Relationship to the student who made the confirmation
    public function student()
    {
        return $this->belongsTo(\Modules\Authentication\Models\Student::class, 'student_id');
    }
}
