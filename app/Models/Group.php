<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    public $timestamps = false;
    protected $table = 'groups';
    protected $dates = ['created_at'];

    protected $fillable = [
        'name',
        'code',
        'description',
        'logo',
        'bannerImage',
        'status',
        'created_at',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'group_id', 'id');
    }
}
