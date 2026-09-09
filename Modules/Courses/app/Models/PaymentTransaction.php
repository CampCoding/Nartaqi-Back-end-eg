<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Authentication\Models\Student;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'invoice_id',
        'student_id',
        'payment_method',
        'amount',
        'currency',
        'status',
        'type',
        'round_id',
        'pay_load',
        'raw_response',
    ];

    protected $casts = [
        'pay_load' => 'array',
        'raw_response' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function round()
    {
        return $this->belongsTo(Rounds::class, 'round_id');
    }
}
