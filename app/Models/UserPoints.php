<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPoints extends Model
{
    protected $table = 'user_points';

    protected $fillable = [
        'user_id', 'entity_type', 'entity_id', 'entity_created_by_id','points', 'created_at', 'updated_at'
    ];

    const LIKE_COMMUNITY_POST = 'like_community_post';
    const LIKE_REVIEW_POST = 'like_review_post';
    const LIKE_SHOP_POST = 'like_shop_post';
    const UPLOAD_COMMUNITY_POST = 'upload_community_post';
    const REVIEW_SHOP_POST = 'review_shop_post';
    const REVIEW_HOSPITAL_POST = 'review_hospital_post';
    const COMMENT_ON_COMMUNITY_POST = 'comment_on_community_post';
    const COMMENT_ON_REVIEW_POST = 'comment_on_review_post';
    const UPLOAD_SHOP_POST = 'upload_shop_post';

    const LIKE_COMMUNITY_OR_REVIEW_POST = 'like_community_or_review_post';
    const ADMIN_GIVE_EXP = 'admin_give_exp';
    const GIVE_REFERRAL_EXP = "give_referral_exp";

    const LIKE_COMMUNITY_POST_POINT = 5;
    const LIKE_REVIEW_POST_POINT = 5;
    const UPLOAD_COMMUNITY_POST_POINT = 10;
    const REVIEW_SHOP_POST_POINT = 200;
    const REVIEW_HOSPITAL_POST_POINT = 200;
    const COMMENT_ON_COMMUNITY_POST_POINT = 10;
    const COMMENT_ON_REVIEW_POST_POINT = 10;
    const LIKE_SHOP_POST_POINT = 10;
    const UPLOAD_SHOP_POST_POINT = 10;
    const LIKE_COMMUNITY_OR_REVIEW_POST_POINT = 5;
}
