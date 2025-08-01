<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'filename',
        'path',
        'url',
        'mime_type',
        'size',
        'imageable_id',
        'imageable_type'
    ];

    /**
     * Get the parent imageable model.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the full public URL for the image.
     */
    public function getFullUrlAttribute()
    {
        return asset('storage/' . $this->path);
    }
}
