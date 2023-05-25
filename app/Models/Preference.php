<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'source_id', 'category_id', 'author_id'
    ];

    /**
     * Get the users associated with the preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
