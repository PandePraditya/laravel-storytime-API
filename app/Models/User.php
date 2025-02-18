<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'profile_image',
        'about',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationship to stories (one-to-many)
    public function stories()
    {
        return $this->hasMany(Story::class);
    }

    // Relationship to bookmarks (one-to-many)
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Relationship to bookmarked stories (many-to-many)
    public function bookmarkedStories()
    {
        return $this->belongsToMany(Story::class, 'bookmarks', 'user_id', 'story_id');
    }
}
