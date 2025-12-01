<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = 'configs';
    public $timestamps = false;
    protected $dates = ['created_at'];
    protected $fillable = [
        'json',
        'status',
        'createdAt',
        'businessId',
        'googleReviewStatus',
        'googleReview',
        'wifiPassword',
        'wifiPasswordStatus',
        'instagramStatus',
        'instagram',
        'review',
        'reviewStatus',
        'stars',
        'starsStatus',
        'googleMapStatus',
        'googleMap',
        'showFeedbackFormStatus',
        'facebookStatus',
        'facebook',
        'youtubeStatus',
        'youtube',
        'whatsappStatus',
        'whatsapp',
        'tripadvisor',
        'tripadvisorStatus'
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'businessId');
    }
}
