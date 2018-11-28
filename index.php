<?php
ini_set("display_errors",1);
error_reporting(E_ALL);
    require "vendor/autoload.php";
    //the task is to check the homepage for three words . University , Federal Goverment and Student , Anytime we see any of these words , we click the article and then fetch the article's title , the article , the date ,the image URL ,the category
    use Goutte\Client;
    $client = new Client();
    $crawler = $client->request('GET','https://punchng.com/topics/news');
    $articles = [];
    $datePublished = [];
    $categories = [];
    $crawler->filter('.seg-title')->each(function ($node) use(&$headings,$crawler,$client,&$articles,&$datePublished,&$categories){
        $link = $crawler->filter("[title='".$node->text()."']")->attr('href');
        $crawler = $client->request('GET',$link);
        $datePublished[] = $crawler->filter(".entry-date.published")->extract('_text');
        $crawler->filter('.tags-links a:not([href])')->each(function($node) use(&$categories){
                $categories[] = $node->text();
                
        });
        $article = "";
        $crawler->filter('div > .entry-content > p')->nextAll()->each(function($node)use(&$article,&$articles){
                // var_dump($node->text());
                $article .= $node->text();
                
        });
        $crawler->filter('.blurry')->
        $articles[] = $article;
        //for each article , do database insertion stuff here
    });
        var_dump($datePublished);
        var_dump($categories);
        var_dump($articles);
//     $client = new Client();
//     $crawler = $client->request('GET','https://punchng.com/topics/news');
//     for($i=0; $i<count($headings); $i++){

//         $crawler->filter("[title='".$headings[$i]."']")->attr('href')->each(function($node){
//                 print $node->text();
//         });

        // $link = $crawler->selectLink($headings[$i])->link();
        // $crawler = $client->click($link);
        // $article = $crawler->filter('article');
        // var_dump($article->text());
//     }
   