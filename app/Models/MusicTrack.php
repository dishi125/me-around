<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusicTrack extends Model
{
    protected $table = "music_tracks";

    protected $fillable = [
        'file',
        'title'
    ];

}
