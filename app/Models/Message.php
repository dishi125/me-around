<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\EntityTypes;
use App\Models\Shop;
use App\Models\Hospital;

class Message extends Model
{
    protected $table = 'messages';

    public $timestamps = false;


    protected $fillable = [
        'entity_type_id ',
        'entity_id',
        'from_user_id',
        'to_user_id ',
        'type',
        'message',
        'status',
        'is_post_image',
        'is_default_message',
        'is_guest',
        'created_at',
        'updated_at',
        'is_admin_message'
    ];

    protected $casts = [
        'entity_type_id ' => 'int',
        'entity_id' => 'int',
        'from_user_id ' => 'int',
        'to_user_id' => 'int',
        'type' => 'string',
        'message' => 'string',
        'status' => 'int',
        'is_post_image' => 'boolean',
        'is_default_message' => 'int',
        'created_at' => 'string',
        'updated_at' => 'string'
    ];

    protected $appends = ['image'];

    public function getImageAttribute()
    {
        $entity_type_id = !empty($this->attributes['entity_type_id']) ? $this->attributes['entity_type_id'] : "";
        $entity_id = !empty($this->attributes['entity_id']) ? $this->attributes['entity_id'] : "";
        if (empty($entity_type_id) && empty($entity_id)) {
            return $this->attributes['image'] = '';
        } else {
            $image = '';
            if($entity_type_id == EntityTypes::SHOP){
                $shop = Shop::find($entity_id);
                $image = $shop && !empty($shop->workplace_images) && !empty($shop->workplace_images[0]) ? $shop->workplace_images[0]->image : '';
            }else{
                $post = Post::find($entity_id);
                if ($post) {
                    $hospital = Hospital::find($post->hospital_id);
                    $image = $hospital && !empty($hospital->images) && !empty($hospital->images[0]) ? $hospital->images[0]->image : '';
                }
            }
            return $this->attributes['image'] = $image;
        }
    }
}
