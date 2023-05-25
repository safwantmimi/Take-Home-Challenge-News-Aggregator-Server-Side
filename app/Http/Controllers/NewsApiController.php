<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\NewsTrait;

class NewsApiController extends Controller
{
    use NewsTrait;

    public function __constructor(){
        $authorizationHeader = \request()->header('Authorization');
            if(isset($authorizationHeader)) {
                $this->middleware('auth:api');
            }
    }

    public function index(Request $request)
    {
        return $this->getNews();
    }

    public function categories()
    {
        return $this->getCategories();
    }

    public function authors()
    {
        return $this->getAuthors();
    }

    public function sources()
    {
        return $this->getSources();
    }

    public function latest()
    {
        return $this->fetchRecentNews();
    }

    public function article_viewed()
    {
        return $this->viewArticle();
    }

    public function weekly()
    {
        return $this->fetchWeeklyNews();
    }
}
