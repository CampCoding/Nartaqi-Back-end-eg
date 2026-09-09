<?php

namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccountsModel extends Model
{
    use HasFactory;

    protected $table = 'bank_accounts';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'bank_name',
        'account_holder_name',
        'account_number',
        'iban',
        'image',
        'created_at'
    ];

    public $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return url('storage/' . $this->image);
        }
        return null;
    }
}
