<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
    ];

    // Relationship to user (many-to-one)
    public function user() {
        return $this->belongsTo(User::class);
    }

    // Relationship to story (many-to-one)
    public function story() {
        return $this->belongsTo(Story::class);
    }
}
