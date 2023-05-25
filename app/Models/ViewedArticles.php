<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Article;

class ViewedArticles extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id', 'user_id', 'created_at'
    ];
    public $timestamps = false;

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }
}
