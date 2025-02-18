<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'title',
        'content',
        'content_images',
        'category_id',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'content_images' => 'array',
        'created_at' => 'datetime',
    ];

    // Relationship to user (many-to-one)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to category (many-to-one)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relationship to bookmarks (one-to-many)
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Relationship to users who bookmarked the story (many-to-many)
    public function bookmarkedByUsers()
    {
        return $this->belongsToMany(User::class, 'bookmarks', 'story_id', 'user_id');
    }
}
