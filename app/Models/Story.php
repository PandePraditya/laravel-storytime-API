<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'content_images',
        'category_id',
        'user_id',
    ];

    protected $casts = [
        'content_images' => 'array'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function bookmarks() {
        return $this->hasMany(Bookmark::class);
    }
}
