<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{

    use SoftDeletes;
    public $timestamps = false;
    protected $table = 'groups';
    protected $dates = ['deleted_at', 'created_at'];

    protected $fillable = [
        'name',
        'description',
        'image',
        'status',
        'created_at',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
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
