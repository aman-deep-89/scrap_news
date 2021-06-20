<?php
header("Content-Type: text/html;charset=utf-8");
ini_set("display_errors",1);
error_reporting(E_ALL);
    require "vendor/autoload.php";
    //the task is to check the homepage for three words . University , Federal Goverment and Student , Anytime we see any of these words , we click the article and then fetch the article's title , the article , the date ,the image URL ,the category
    use Goutte\Client;
    function saveInDatabase($postTitle,$postBody,$postImageURL,$postDate,$postCategory,$postLink){
        $host = "localhost";
        $dbName = "wpmajor_db";
        $username = "root";
        $password = "";
        $db = new PDO("mysql:host=$host;dbname=$dbName",$username,$password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $time = date('Y-m-d H:i:s');
        $preQuery = "SET NAMES 'utf8'";
        $preparatory = $db->prepare($preQuery);
        $preparatory->execute();
        $query = "SELECT COUNT(*) FROM news WHERE title = ? ";
        $preparation = $db->prepare($query);
        $preparation->execute([$postTitle]);
        if($preparation->fetch()[0] < 1){
                $sql = "INSERT INTO news (title,content,category,url,image,published_date) VALUES (?,?,?,?,?,?) ";
                $prepared = $db->prepare($sql);
                $executed = $prepared->execute([$postTitle,$postBody,rtrim($postCategory,','),$postLink,$postImageURL,$time]);
                if($executed){
                        echo " Saved in the database ";
                }
        }
        
     }
    function scrapePunch($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.vanguardngr.com/category/'.$newsCategory :'https://www.vanguardngr.com/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
               // echo $punchPage; exit;
               $links=$crawler->filter('.rtp-latest-cat-post .entry-title a')->extract('href');
               $link=$links[0];
               $title=$crawler->filter('.rtp-latest-cat-post .entry-title a');
               $postTitle = $title->text();
               $crawler = $client->request('GET',$link);
               $dt=$crawler->filter('time.entry-date.published');
               $date=$dt->attr("datetime");
                $datePublished[] = strtotime($date);
                //echo 'dt='.$date;
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $articleDay = date('z',$articleTimestamp);
                $currentDay = date('z');
                //echo $postTitle.'='.$link.'='.$date; exit;
                //echo $articleDay.'='.$currentDay; exit;
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.entry-content p:not(:first-child)')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                //echo 'art='.$article; exit;
                    if(true) {
                            $categories = "";
                            $crawler->filter('.rtp-meta-cat a')->each(function($node) use(&$categories){
                                    $categories .= $node->text() . ",";
                            });
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                            echo '-------<br/>Title='.$postTitle;
                            echo '<br/>Article='.$article;
                            echo '<br/>URL='.$imageURL;
                            echo '<br/>Date='.$date;
                            echo '<br/>cat='.$categories;
                            echo '<br/>Link='.$link; 
                            //exit;
                            
                                  //  saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                            }else{
                                    //$imager = $imaged[0].".png";
                                    //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                  //  saveInDatabase($postTitle,$article,'',$date[0],$categories,$link);   
                            }
                             
                    }
                }else{
                        //we do not want to continue and we break out .
                        $i = 500;
                        continue;
                }
                $crawler->filter('.rtp-listing-post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                       $links=$crawler->filter('.rtp-latest-cat-post .entry-title a')->extract('href');
               $link=$links[0];
               $title=$crawler->filter('.rtp-latest-cat-post .entry-title a');
               $postTitle = $title->text();
               $crawler = $client->request('GET',$link);
               $dt=$crawler->filter('time.entry-date.published');
               $date=$dt->attr("datetime");
                $datePublished[] = strtotime($date);
                //echo 'dt='.$date;
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $articleDay = date('z',$articleTimestamp);
                $currentDay = date('z');
                //echo $postTitle.'='.$link.'='.$date; exit;
                //echo $articleDay.'='.$currentDay; exit;
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.entry-content p:not(:first-child)')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                //echo 'art='.$article; exit;
                    if(true) {
                            $categories = "";
                            $crawler->filter('.rtp-meta-cat a')->each(function($node) use(&$categories){
                                    $categories .= $node->text() . ",";
                            });
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                            echo '-------<br/>Title='.$postTitle;
                            echo '<br/>Article='.$article;
                            echo '<br/>URL='.$imageURL;
                            echo '<br/>Date='.$date;
                            echo '<br/>cat='.$categories;
                            echo '<br/>Link='.$link; 
                            //exit;
                            
                                  //  saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                            }else{
                                    //$imager = $imaged[0].".png";
                                    //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                  //  saveInDatabase($postTitle,$article,'',$date[0],$categories,$link);   
                            }
                             
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
    function scrapeVanguardngr($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.vanguardngr.com/category/'.$newsCategory :'https://www.vanguardngr.com/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
               // echo $punchPage; exit;
               $links=$crawler->filter('.rtp-latest-cat-post .entry-title a')->extract('href');
               $link=$links[0];
               $title=$crawler->filter('.rtp-latest-cat-post .entry-title a');
               $postTitle = $title->text();
               $crawler = $client->request('GET',$link);
               $dt=$crawler->filter('time.entry-date.published');
               $date=$dt->attr("datetime");
                $datePublished[] = strtotime($date);
                //echo 'dt='.$date;
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $articleDay = date('z',$articleTimestamp);
                $currentDay = date('z');
                //echo $postTitle.'='.$link.'='.$date; exit;
                //echo $articleDay.'='.$currentDay; exit;
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.entry-content p:not(:first-child)')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                //echo 'art='.$article; exit;
                    if(true) {
                            $categories = "";
                            $crawler->filter('.rtp-meta-cat a')->each(function($node) use(&$categories){
                                    $categories .= $node->text() . ",";
                            });
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date[0],$categories,$link);   
                            }
                             
                    }
                }
                $i++;
                $crawler = $client->request('GET',$punchPage);
                //echo 'page='.$punchPage; exit;
                $crawler->filter('.rtp-listing-post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                       $links=$crawler->filter('.rtp-listing-post .entry-title a')->extract('href');
               $link=$links[0];
               $title=$crawler->filter('.rtp-listing-post .entry-title a');
               $postTitle = $title->text();
               $crawler = $client->request('GET',$link);
               $dt=$crawler->filter('time.entry-date.published');
               $date=$dt->attr("datetime");
                $datePublished[] = strtotime($date);
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $articleDay = date('z',$articleTimestamp);
                $currentDay = date('z');
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.entry-content p:not(:first-child)')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                    if(true) {
                            $categories = "";
                            $crawler->filter('.rtp-meta-cat a')->each(function($node) use(&$categories){
                                    $categories .= $node->text() . ",";
                            });
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date[0],$categories,$link);   
                            }
                             
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
    scrapePunch("entertainment/",2);