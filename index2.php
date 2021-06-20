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
                                        $imaged = explode('-30',$image);
                                        $imager = $imaged[0].".jpg";
                                        $imageURL = str_replace("').jpg","",strstr($imager,"h")); 
                                        //save it to the database
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                                        }else{
                                                $imager = $imaged[0].".png";
                                                $imageURL = str_replace("').png","",strstr($imager,"h")); 
                                                saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
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
    function scrapeBusinessdayonline($newsCategory,$numberOfPagesToScrape) {
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://businessday.ng/category/news/'.$newsCategory :'https://businessday.ng/category/news/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $crawler->filter('.main-section article')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $title = $node->filter('.title');
                        $postTitle=$title->text();
                        $links = $node->filter('.title > a')->extract('href');
                        $link = $links[0];
                        //echo $link; exit;
                        $crawler = $client->request('GET',$link);
                        $date = $crawler->filter("span.time .post-published")->extract('datetime');
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
                                if(true) {
                                        $categories = "";
                                        $crawler->filter('.bf-breadcrumb-item a')->each(function($node) use(&$categories){
                                                $categories = $node->text() . ",";
                                        });
                                        $image='';
                                        $crawler->filter('.single-featured img')->each(function($node) use(&$image){
                                                $image = $node->attr('data-src'). ",";
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date[0]=date('Y-m-d H:i:s',$articleTimestamp);
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

    function scrapeDailytrust($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://dailytrust.com/topics/'.$newsCategory :'https://dailytrust.com/topics'.$newsCategory;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $crawler->filter('.topics_content__l3Giv .list_list__4Bgec')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('.list_card__MiAg3  a')->extract('href');
                        $link = $links[1];
                        $title = $node->filter('.list_card__MiAg3  a')->eq(1);
                        $postTitle = $title->text();
                        $crawler = $client->request('GET','https://dailytrust.com'.$link);
                        $dt = $crawler->filter(".Page_time__3YjtX");
                        $date=$dt->text();
                        $datePublished[] = $date;
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.typography_body__32m2U > p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                if(true) {
                                        $categories = "";
                                        $crawler->filter('.tags_tags__1QWPp a')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                        });
                                        $image='';
                                        $crawler->filter('.Page_image__fYZcD img')->each(function($node) use(&$image){
                                                $image = $node->attr('data-src'). ",";
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        if(file_get_contents('https://dailytrust.com'.$imageURL)){
                                        /*echo '-------<br/>Title='.$postTitle;
                                        echo '<br/>Article='.$article;
                                        echo '<br/>URL='.$imageURL;
                                        echo '<br/>Date='.$date;
                                        echo '<br/>cat='.$categories;
                                        echo '<br/>Link='.$link; exit;*/
                                        
                                                saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,'https://dailytrust.com'.$link);   
                                        }else{
                                                //$imager = $imaged[0].".png";
                                                //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                                saveInDatabase($postTitle,$article,'',$date[0],$categories,'https://dailytrust.com'.$link);   
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

    function scrapeGuardian($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://guardian.ng/category/'.$newsCategory :'https://guardian.ng/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                //echo $punchPage;
                $crawler->filter('.widget_guardian_listing .row .cell')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('span.title');
                        $postTitle = $title->text();
                        //echo $postTitle.'='.$link; exit;
                        $crawler = $client->request('GET',$link);
                        $dt = $crawler->filter(".single-article-datetime");
                        $date=$dt->text();
                        $datePublished[] = str_replace('|',' ',$date);
                        $dt_temp=DateTime::createFromFormat('d F Y  h:i a', '17 April 2021 12:09 pm');
                        $dt2=$dt_temp->format('Y-m-d H:i:s');
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($dt2);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.single-article-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                /*echo $date;
                                echo 'article='.$article; exit;*/
                                //check if string exists in the body 
                                //if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                        $categories = "";
                                        $crawler->filter('.tags a')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                        });
                                        $image='';
                                        $crawler->filter('.single-article-content article div img')->each(function($node) use(&$image){
                                                $image = $node->attr('src');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL;
                                        if(file_get_contents($imageURL)){
                                        /*echo '-------<br/>Title='.$postTitle;
                                        echo '<br/>Article='.$article;
                                        echo '<br/>URL='.$imageURL;
                                        echo '<br/>Date='.$date;
                                        echo '<br/>cat='.$categories;
                                        echo '<br/>Link='.$link; exit;*/
                                        
                                              //  saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                                        }else{
                                                //$imager = $imaged[0].".png";
                                                //$imageURL = str_replace("').png","",strstr($imager,"h")); 
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
    function scrapeIndependent($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.independent.ng/category/'.$newsCategory :'https://www.independent.ng/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                //echo $punchPage; exit;
                if($i==1) {
                $crawler->filter('.listing-modern-grid article.listing-item')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('h2.title');
                        $postTitle = $title->text();
                        //echo $postTitle.'='.$link; exit;
                        $crawler = $client->request('GET',$link);
                        $dt = $crawler->filter(".single-post-meta time");
                        $date=$dt->attr("datetime");
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.post.single-post-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                /*echo $date;
                                echo 'article='.$article; exit;*/
                                //check if string exists in the body 
                                //if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                        $categories = "";
                                        $crawler->filter('.bf-breadcrumb-items li a')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                        });
                                        $image='';
                                        $crawler->filter('.single-featured a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL;
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
            } 
            // archived posts
            $crawler->filter('.listing-blog article')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[1];
                        $title = $node->filter('h2.title');
                        $postTitle = $title->text();
                        //echo $postTitle.'='.$link; exit;
                        $crawler = $client->request('GET',$link);
                        $dt = $crawler->filter(".single-post-meta time");
                        $date=$dt->attr("datetime");
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                                $article = "";
                                $crawler->filter('.post.single-post-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                /*echo $date;
                                echo 'article='.$article; exit;*/
                                //check if string exists in the body 
                                //if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                        $categories = "";
                                        $crawler->filter('.bf-breadcrumb-items li a')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                        });
                                        $image='';
                                        $crawler->filter('.single-featured a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL;
                                        if(file_get_contents($imageURL)){
                                         saveInDatabase($postTitle,$article,$imageURL,$date[0],$categories,$link);   
                                        }else{
                                                //$imager = $imaged[0].".png";
                                                //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                                saveInDatabase($postTitle,$article,'',$date[0],$categories,$link);   
                                        }                                         
                                }                        
                   $j++;     
                });            
               $i++;
        }
    } 
    scrapePunch("news",5);
    scrapeIndependent("business/abuja-business/",2,5);
    