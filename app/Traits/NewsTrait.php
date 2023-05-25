<?php
namespace App\Traits;

use App\Models\Article;
use App\Models\Source;
use App\Models\Category;
use App\Models\Author;
use App\Models\ViewedArticles;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Elasticsearch\ClientBuilder;
use Carbon\Carbon;

trait NewsTrait
{

    #Author: Safouan Tmimi 

    public function getNews()
    {
        $user = Auth::user();
    
        // Get user preferences
        if ($user) {
            if ($user->preferences()->exists()) {
                $preferences = $user->preferences()->get();
            } else {
                $preferences = null;
            }
        }
    
        $query = Article::with(['source', 'author'])->latest();
    
        // Apply user preferences
        if ($user && $preferences) {

            $sources = $preferences->pluck('source_id');
            $categories = $preferences->pluck('category_id');
            $authors = $preferences->pluck('author_id');

            if(!$sources->contains('[]')){
                $query->whereHas('source', function ($query) use ($preferences,$sources) {
                    $query->whereIn('id', json_decode($sources->first()));
                });
            }
            if(!$categories->contains('[]')){
                $query->whereHas('source.category', function ($query)  use ($preferences,$categories) {
                    $query->whereIn('id', json_decode($categories->first()));
                });
            }

            if(!$authors->contains('[]')){
                $query->whereHas('author', function ($query)  use ($preferences,$authors) {
                    $query->whereIn('id', json_decode($authors->first()));
                });
            }
        }
        // Apply text search
        $searchQuery = request()->input('search_query');
        $publishedDate = null;
        if ($searchQuery) {

            // Check if the search query contains a valid date
            $date = date_create_from_format('d/m/Y', $searchQuery);
            if ($date !== false) {
                // Extract the date and remove it from the search query
                $publishedDate = $date->format('Y-m-d');
                $searchQuery = str_replace($date->format('d/m/Y'), '', $searchQuery);
                $searchQuery = trim($searchQuery);
            }


            $query->where(function ($query) use ($searchQuery) {
                $query->where('title', 'like', "%$searchQuery%")
                    ->orWhere('content', 'like', "%$searchQuery%")
                    ->orWhereHas('source', function ($query) use ($searchQuery) {
                        $query->where('name', 'like', "%$searchQuery%");
                    })
                    ->orWhereHas('source.category', function ($query) use ($searchQuery) {
                        $query->where('name', 'like', "%$searchQuery%");
                    })
                    ->orWhereHas('author', function ($query) use ($searchQuery) {
                        $query->where('name', 'like', "%$searchQuery%");
                    });

           
            });
        }
    
        // Apply filter for specific published date if exists
        if ($publishedDate) {
            $query->whereDate('published_at', '=', $publishedDate);
        }
        // Apply filters
        if (request()->has('category')) {
            $category = request()->input('category');
            $query->whereHas('source', function ($query) use ($category) {
                $query->where('category_id', $category);
            });
        }
    
        if (request()->has('country')) {
            $country = request()->input('country');
            $query->whereHas('source', function ($query) use ($country) {
                $query->where('country', $country);
            });
        }
    
        if (request()->has('from_date')) {
            $fromDate = request()->input('from_date');
            $query->whereDate('published_at', '>=', $fromDate);
        }
    
        // Get the filtered articles
        $articles = $query->orderByDesc('id')->get();
    
        // Retrieve 3 similar articles for each article based on author or source
        $relatedArticles = [];
        foreach ($articles as $article) {
            $relatedQuery = Article::where(function ($query) use ($article) {
                $query->where('author_id', $article->author_id)
                    ->orWhereHas('source', function ($query) use ($article) {
                        $query->where('id', $article->source_id);
                    });
            })
                ->where('id', '!=', $article->id)
                ->whereNotNull('published_at')
                ->whereNotIn('title', [$article->title])
                ->whereNotIn('content', [$article->content])
                ->inRandomOrder() // Order randomly
                ->limit(3)
                ->get();
    
            $relatedArticles[$article->id] = $relatedQuery;
        }
    
        // Assign related articles to each article
        foreach ($articles as $article) {
            $article->related_articles = $relatedArticles[$article->id] ?? [];
        }
    
        // Select the author_name and source_name fields
        $articles = $articles->map(function ($article) {
            $article->author_name = $article->author->name;
            $article->source_name = $article->source->name;
            return $article;
        });
    
        return [
            'results' => response()->json($articles->load('source', 'author')),
            'total' => $articles->count(),
        ];
    }


    public function fetchRecentNews()
    {
        $articles = Article::whereNotNull('image')
            ->inRandomOrder()
            ->take(5)
            ->get();

        return response()->json($articles->load('source', 'author'));
    }
    

    public function fetchWeeklyNews()
    {
        // Calculate the start of the current week
        $startOfWeek = Carbon::now()->startOfWeek();

        $articles = [];

        // Fetch 50 news articles from the beginning of the current week
        $query = Article::join('sources', 'articles.source_id', '=', 'sources.id')
            ->where('articles.published_at', '>=', $startOfWeek)
            ->whereNotNull('image')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        foreach ($query as $article) {
            $articles[] = $article;
        }

        return response()->json($articles);
    }

    
    
    
    public function getAuthors()
    {
        $authors = Author::all();
    
        return response()->json($authors);
    }
    
    public function getSources()
    {
        $sources = Source::all();
    
        return response()->json($sources);
    }
    

    public function getCategories()
    {
        $categories = Category::get(['name','id']);
        return response()->json($categories);
    }

    public function viewArticle()
    {
        // Get the currently authenticated user
        $user = Auth::user();

        ViewedArticles::create([
            'article_id' => request()->get('article_id'),
            'user_id' => $user->id,
            'created_at' => now()
        ]);
    }
}
