<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'content', 'url','image', 'published_at', 'source_id', 'category_id'
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function viewedArticle()
    {
        return $this->belongsTo(ViewedArticle::class, 'viewed_articles_id');
    }
}
