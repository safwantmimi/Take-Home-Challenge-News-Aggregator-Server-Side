<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use App\Models\Article;
use App\Models\Source;
use App\Models\Author;
use App\Models\Category;
class FetchNewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch-news';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches and stores news from the different sources';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $fromDate = Carbon::today()->format('d/m/Y');
        $newsApiKey = getenv('NEWS_API_KEY');
        $newYorkTimesApiKey = getenv('NYK_API_KEY');
        $GNewsApiKey = getenv('GNEWS_API_KEY');
        
        // Array of API sources and their respective endpoints and parameters
        $sources = [
            'newsapi' => [
                'endpoint' => 'https://newsapi.org/v2/everything',
                'parameters' => [
                    'q' => '*',
                    'from' => $fromDate,
                    'sortBy' => 'popularity',
                    'apiKey' => $newsApiKey
                ]
            ],
            'gnews' => [
                'endpoint' => 'https://gnews.io/api/v4/search?',
                'parameters' => [
                    'q' => '*',
                    'from' => $fromDate,
                    'sortBy' => 'popularity',
                    'apiKey' => $GNewsApiKey
                ]
            ],
            'guardian' => [
                'endpoint' => 'https://content.guardianapis.com/search?api-key=test',
                'parameters' => [
                    'api-key' => 'test'
                ]
            ],
            'nytimes' => [
                'endpoint' => 'https://api.nytimes.com/svc/search/v2/articlesearch.json',
                'parameters' => [
                    'q' => '*',
                    'api-key' => $newYorkTimesApiKey,
                ]
            ]
            // Add more sources as needed
        ];
        
        // Fetch and store news from each source
        foreach ($sources as $sourceName => $source) {
            $response = Http::get($source['endpoint'], $source['parameters']);
            if(!empty($response->json()['articles']))
            {
                $articles = $response->json()['articles'];
            }
            else if(!empty($response->json()['results']))
            {
                $articles = $response->json()['results'];
            }
            else if(!empty($response->json()['response']))
            {
                // var_dump($response->json()['response']);
                $articles = isset($response->json()['response']['results']) ? $response->json()['response']['results'] : (isset($response->json()['response']['docs']) ? $response->json()['response']['docs'] : []);
            }
            
            // Iterate through the articles and store them in the associated tables
            foreach ($articles as $articleData) {
                // Create new model instances and populate the data
                $article = new Article();
                $article->title = isset($articleData['title']) ? $articleData['title'] : (isset($articleData['webTitle']) ? $articleData['webTitle'] : ($articleData['headline']['main']));
                $article->content = isset($articleData['content']) ? $articleData['content'] : (isset($articleData['webTitle']) ? $articleData['webTitle'] : ($articleData['lead_paragraph']));
                $article->published_at = isset($articleData['publishedAt']) ? $articleData['publishedAt'] : (isset($articleData['webPublicationDate']) ? $articleData['webPublicationDate'] : ($articleData['pub_date']));
                $article->description = isset($articleData['description']) ? $articleData['description'] : (isset($articleData['webTitle']) ? $articleData['webTitle'] : ($articleData['snippet']));
                $article->url = isset($articleData['url']) ? $articleData['url'] : (isset($articleData['webUrl']) ? $articleData['webUrl'] : ($articleData['web_url']));
                $article->image = isset($articleData['urlToImage']) ? $articleData['urlToImage'] : (isset($articleData['apiUrl']) ? $articleData['apiUrl'] : ($articleData['multimedia'][0]['url']));
                
                // Associate the article with a source
                if (isset($articleData['source']) || isset($articleData['sectionName'])) {
                    $source = Source::firstOrCreate([
                        'name' => isset($articleData['source']) ? $articleData['source']['name'] : (isset($articleData['sectionName']) ? $articleData['sectionName']: ''),
                    ]);
                    $article->source()->associate($source);
                }
                
                // Associate the article with an author
                if (isset($articleData['author']) || isset($articleData['pillarName']) || isset($articleData['headline'])) {
                    $author = Author::firstOrCreate([
                        'name' => isset($articleData['author']) ? $articleData['author'] :( isset($articleData['pillarName']) ? $articleData['pillarName'] : $articleData['headline']['kicker']),
                    ]);
                    $article->author()->associate($author);
                } else { // Author not provided from the API
                    $author = Author::firstOrCreate([
                        'name' => 'unknown'
                    ]);
                    $article->author()->associate($author);
                }
                

                // Save the article to the database
                $article->save();
            }
        }

        $this->info('News fetched and stored.');
    }

    public function fetchSources()
{
    $apiKey = getenv('NEWS_API_KEY');
    $response = Http::get('https://newsapi.org/v2/sources', [
        'apiKey' => $apiKey,
    ]);
    $sourcesData = $response->json()['sources'];

    foreach ($sourcesData as $sourceData) {
        // Create new model instance and populate the data
        $source = new Source();
        $source->name = $sourceData['name'];
        $source->description = $sourceData['description'];
        $source->url = $sourceData['url'];
        $source->language = $sourceData['language'];
        $source->country = $sourceData['country'];

        // Associate the source with a category
        if ($sourceData['category']) {
            $category = Category::firstOrCreate(['name' => $sourceData['category']]);
            $source->category()->associate($category);
        }

        // Save the source to the database
        $source->save();
    }
}

}
