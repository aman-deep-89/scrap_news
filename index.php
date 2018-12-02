<?php
ini_set("display_errors",1);
error_reporting(E_ALL);
    require "vendor/autoload.php";
    //the task is to check the homepage for three words . University , Federal Goverment and Student , Anytime we see any of these words , we click the article and then fetch the article's title , the article , the date ,the image URL ,the category
    use Goutte\Client;
    function saveInDatabase($postTitle,$postBody,$postImageURL,$postDate,$postCategory,$postLink){
        $host = "localhost";
        $dbName = "wpmajor_db";
        $username = "root";
        $password = "jesussaves";
        $db = new PDO("mysql:host=$host;dbname=$dbName",$username,$password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO news (title,content,category,url,image,published_date) VALUES (?,?,?,?,?,?) ";
        $prepared = $db->prepare($sql);
        $executed = $prepared->execute([$postTitle,$postBody,rtrim($postCategory,','),$postLink,$postImageURL,$time]);
        if($executed){
                echo " Saved in the database ";
        }
     }
    function scrapePunch($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://punchng.com/topics/'.$newsCategory :'https://punchng.com/topics/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $crawler->filter('.seg-title')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $postTitle = $node->text();
                        $links = $crawler->filter('.items.col-sm-12 > a')->extract('href');
                        $link = $links[$j];
                        $crawler = $client->request('GET',$link);
                        $date = $crawler->filter(".entry-date.published")->extract('_text');
                        $datePublished[] = $date[0];
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date[0]);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.entry-content > p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->text(); 
                                });
                                //check if string exists in the body 
                                if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                        $categories = "";
                                        $crawler->filter('.tags-links a:not([href])')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                
                                        });
                        
                                        $image = $crawler->filter('.blurry')->eq(0)->attr('style');
                                        $imageURL = strstr(strstr($image,"h"),"'",TRUE) . "<br><br>"; 
                                        //save it to the database
                                        saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);    
                                }
                                
                        }else{
                                //we do not want to continue and we break out .
                                $i = 500;
                        }
                        
                       
                   $j++;     
                });
               $i++;
        }
    } 
    scrapePunch("news",3);