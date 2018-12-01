<?php
ini_set("display_errors",1);
error_reporting(E_ALL);
    require "vendor/autoload.php";
    //the task is to check the homepage for three words . University , Federal Goverment and Student , Anytime we see any of these words , we click the article and then fetch the article's title , the article , the date ,the image URL ,the category
    use Goutte\Client;
    /* function saveInDatabase($postTitle,$postBody,$postImageURL,$postDate,$postCategory){

     } */
    function scrapePunch($newsCategory){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < 5 ){
               /*  $punchPage = $i <= 1 ? 'https://punchng.com/topics/'.$newsCategory :'https://punchng.com/topics/'.$newsCategory.'/page/'.$i; */
               $punchPage = "https://punchng.com/topics/news/page/3/";
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $categories = [];
                $j = 0;
                $crawler->filter('.seg-title')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j){
                        print $i ." " . $node->text() . "<br> <br>";
                        // var_dump($node);
                        // $link = $crawler->filter('div.items > a[title="'.$node->text().'"]')->extract('href');
                        $links = $crawler->filter('.items.col-sm-12 > a')->extract('href');
                        $link = $links[$j];
                        print $j . "<br> <br>";
                        var_dump($link);
                        $crawler = $client->request('GET',$link);
                        $date = $crawler->filter(".entry-date.published")->extract('_text');
                        var_dump($date);
                        $datePublished[] = $date[0];
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date[0]);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        // var_dump($articleDay); 
                        if( $articleDay + 1 >= $currentDay  ){
                                // echo "true";
                                $article = "";
                                $crawler->filter('.entry-content > p')->each(function($node)use(&$article,&$articles){
                                        // var_dump($node->text());
                                        $article .= $node->text();
                                        
                                });
                                $crawler->filter('.tags-links a:not([href])')->each(function($node) use(&$categories){
                                        $categories[] = $node->text();
                                        // var_dump($categories);
                                        
                                });
                                
                                $image = $crawler->filter('.blurry')->eq(0)->attr('style');
                                $imageURL = strstr(strstr($image,"h"),"'",TRUE) . "<br><br>";
                                // var_dump($imageURL);
                                $articles[] = $article;
                                //for each article , do database insertion stuff here
                        }else{
                                //we do not want to continue and we break out .
                                $i = 500;
                        }
                        
                       
                   $j++;     
                });
               $i++;
        }
    } 
    scrapePunch("news");
    
    
       /*  var_dump($datePublished);
        var_dump($categories);
        print_r($articles); */
