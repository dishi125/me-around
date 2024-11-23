<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GifticonDetail extends Model
{
    protected $table = 'gifticon_details';

    protected $fillable = [
        'user_id',
        'title',
        'is_new',
        'created_at',
        'updated_at'
    ];

    public function attachments() {
        return $this->hasMany(GifticonAttachment::class, 'gifticon_id', 'id')->orderBy('created_at','DESC');
    }
}
