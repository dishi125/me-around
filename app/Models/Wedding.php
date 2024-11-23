<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wedding extends Model
{
    protected $table = 'weddings';

    protected $dates = ['deleted_at'];

    const FIELD_LIST = [
        "his_name" => ["label" => "His Name","type" => "text", "col" => 4,'required' => true],
        "her_name" => ["label" => "Her Name","type" => "text", "col" => 4,'required' => true],
        "wedding_date" => ["label" => "Wedding Date", "type" => "date", "col" => 4,'required' => true],
        "audio_file" => ["label" => "Audio", "type" => "select", "col" => 4],
        "video_file" => ["label" => "Video Animation", "type" => "select", "col" => 4],
        "design" => ["label" => "Design", "type" => "select", "col" => 4],
        "wedding_details" => ["label" => "Wedding Details", "type" => "textarea", "col" => 6],
        "invite_text" => ["label" => "Invite Text", "type" => "textarea", "col" => 6],

        "son_of" => ["label" => "Son Of", "type" => "text", "col" => 6],
        "daughter_of" => ["label" => "Daughter Of", "type" => "text", "col" => 6],

        "bridegroom_contact" => ["label" => "Bridegroom Contact", "type" => "repeater", "col" => 12, "is_dynamic" => false, "field_group" => [
           [ "name" => ["label" => "Name", "type" => "text", "col" => 3, "index" => 0],
            "number" => ["label" => "Number", "type" => "text", "col" => 3, "index" => 0],
            "email" => ["label" => "Email Address", "type" => "text", "col" => 4, "index" => 0]],
           [ "name" => ["label" => "Name", "type" => "text", "col" => 3, "index" => 1],
            "number" => ["label" => "Number", "type" => "text", "col" => 3, "index" => 1],
            "email" => ["label" => "Email Address", "type" => "text", "col" => 4, "index" => 1]]
        ]],

        "bride_contact" => ["label" => "Bride Contact", "type" => "repeater", "col" => 12, "is_dynamic" => false, "field_group" => [
           [ "name" => ["label" => "Name", "type" => "text", "col" => 3, "index" => 0],
            "number" => ["label" => "Number", "type" => "text", "col" => 3, "index" => 0],
            "email" => ["label" => "Email Address", "type" => "text", "col" => 4, "index" => 0],],
           [ "name" => ["label" => "Name", "type" => "text", "col" => 3, "index" => 1],
            "number" => ["label" => "Number", "type" => "text", "col" => 3, "index" => 1],
            "email" => ["label" => "Email Address", "type" => "text", "col" => 4, "index" => 1],]
        ]],

        "bridegroom_bank" => ["label" => "Bridegroom Bank Detail", "type" => "repeater", "col" => 12, "is_dynamic" => true, "field_group" => [
            [ "bank_name" => ["label" => "Bank Name", "type" => "text", "col" => 3, "index" => 0],
             "account_number" => ["label" => "Account Number", "type" => "text", "col" => 3, "index" => 0],
             "name" => ["label" => "Name", "type" => "text", "col" => 4, "index" => 0]
            ]
         ]],

         "bride_bank" => ["label" => "Bride Bank Detail", "type" => "repeater", "col" => 12, "is_dynamic" => true, "field_group" => [
            [ "bank_name" => ["label" => "Bank Name", "type" => "text", "col" => 3, "index" => 0],
             "account_number" => ["label" => "Account Number", "type" => "text", "col" => 3, "index" => 0],
             "name" => ["label" => "Name", "type" => "text", "col" => 4, "index" => 0]
            ]
         ]],

        "notice_title" => ["label" => "Notice Heading", "type" => "text", "col" => 6],
        "notice_text" => ["label" => "Notice content", "type" => "textarea", "col" => 6],

        "address_details" => ["label" => "Address Detail", "type" => "text", "col" => 7],
        "bus_details" => ["label" => "Bus Detail", "type" => "text", "col" => 5],
        "address" => ["label" => "Enter Location", "type" => "address", "col" => 7],
        "address-latitude" => ["label" => "latitude", "type" => "hidden", "col" => 6],
        "address-longitude" => ["label" => "longitude", "type" => "hidden", "col" => 6],
        // Photo
        "wedding_photo" => ["label" => "Wedding Photo", "type" => "file", "is_multiple" => false, "col" => 6],
        "photo" => ["label" => "Photo", "type" => "file", "is_multiple" => false, "col" => 6],
        "wedding_gallery" => ["label" => "Wedding Gallery", "type" => "file", "is_multiple" => true, "col" => 6],
    ];

    protected $fillable = [
        'name',
        'uuid',
        'created_at',
        'updated_at'
    ];

    public function weddingMeta() {
        return $this->hasMany(WeddingMetaData::class, 'wedding_id', 'id')->orderBy('created_at','ASC');
    }
    public function weddingGuests() {
        return $this->hasMany(WeddingGuestDetail::class, 'wedding_id', 'id')->orderBy('created_at','DESC');
    }
}
