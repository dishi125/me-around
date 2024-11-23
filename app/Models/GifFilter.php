<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GifFilter extends Model
{
    protected $table = "gif_filters";

    protected $fillable = [
      'file',
      'title'
    ];
}
