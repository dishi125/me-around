<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalDoctor extends Model
{
    protected $table = 'hospital_doctors';

    public $timestamps = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hospital_id',
        'doctor_id',
    ];



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'hospital_id' => 'int',
        'doctor_id' => 'int',
    ];
}
