<?php
namespace Modules\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HonorRollModel extends Model
{
    use HasFactory;

    protected $table = 'honor_rolls';

    protected $fillable = [
        'name',
        'title',
        'degree',
        'sort_number'
    ];
}
