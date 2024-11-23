<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamTests extends Model
{
    protected $table = 'exam_tests';

    protected $fillable = [
        'name',
        'created_at',
        'updated_at'
    ];
}
