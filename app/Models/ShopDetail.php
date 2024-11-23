<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Storage;

class ShopDetail extends Model
{
    use SoftDeletes;
    protected $table = 'shop_details';
    protected $dates = ['deleted_at']; 
    
    protected $fillable = [
        'shop_id','description','type','attachment','recycle_type','recycle_option_id','created_at','updated_at'
    ];

    const SINGLE_USE = 'single_use';
    const RECYCLE = 'recycle';

    const TYPE_CERTIFICATE = 'certificate';
    const TYPE_TOOLS_MATERIAL_INFO = 'tools_material_info';
    const TYPE_MENTION = 'mention';

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        $value = $this->attributes['attachment'];
        if (empty($value)) {
            return $this->attributes['attachment_url'] = '';
        } else {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                return $this->attributes['attachment_url'] = Storage::disk('s3')->url($value);
            } else {
                return $this->attributes['attachment_url'] = $value;
            }
        }
    }
}
