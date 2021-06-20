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
       // $time = date('Y-m-d H:i:s');
        $preQuery = "SET NAMES 'utf8'";
        $preparatory = $db->prepare($preQuery);
        $preparatory->execute();
        $query = "SELECT COUNT(*) FROM news WHERE title = ? ";
        $preparation = $db->prepare($query);
        $preparation->execute([$postTitle]);
        if($preparation->fetch()[0] < 1){
                $sql = "INSERT INTO news (title,content,category,url,image,published_date) VALUES (?,?,?,?,?,?) ";
                $prepared = $db->prepare($sql);
                $executed = $prepared->execute([$postTitle,$postBody,rtrim($postCategory,','),$postLink,$postImageURL,$postDate]);
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
                        $categories = "";
                        $cat=$node->filter('.heading-title');
                        $categories = $cat->text() . ",";
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
                               // if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                       
                                        
                                        $image = $crawler->filter('.blurry')->eq(0)->attr('style');
                                        $imaged = explode('-30',$image);
                                        $imager = $imaged[0].".jpg";
                                        $imageURL = str_replace("').jpg","",strstr($imager,"h")); 
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //save it to the database
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                                $imager = $imaged[0].".png";
                                                $imageURL = str_replace("').png","",strstr($imager,"h")); 
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
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
                                        $cat=$crawler->filter('.typography_categoty__title__2ObJB a');
                                        $categories = $cat->text();
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
                                        
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,'https://dailytrust.com'.$link);   
                                        }else{
                                                //$imager = $imaged[0].".png";
                                                //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                                saveInDatabase($postTitle,$article,'',$date,$categories,'https://dailytrust.com'.$link);   
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
                $categories='';
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
                //echo $punchPage;
                $crawler->filter('.widget_guardian_listing .row .cell')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('span.title');
                        $postTitle = $title->text();
                        $image_path='';
                        $node->filter('.image img')->each(function($node1) use(&$image_path){
                                    $image_path = $node1->attr('src');
                        });
                        $image_url=$image_path;
                        //echo $postTitle.'='.$link; exit;
                        $crawler = $client->request('GET',$link);
                        $dt = $crawler->filter(".single-article-datetime");
                        $date=$dt->text();
                        $nbsp = html_entity_decode("&nbsp;");
                        $datePublished[] = str_replace(array('|',$nbsp),' ',$date);
                        $dt3=explode(' ',$datePublished[0]);
                        $dt3=array_map('trim',$dt3);
                        $dt4=implode(' ',$dt3);
                        //echo $dt4; exit;
                        $dt_temp=DateTime::createFromFormat('d F Y h:i a', $dt4);
                        $dt2=$dt_temp->format('Y-m-d H:i:s');
                        $articleTimestamp = strtotime($dt2);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');                        
                        /*$cat=$node->filter('.meta .category');
                        $categories = $cat->text();*/
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.single-article-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                if(true) {
                                        /*$categories = "";
                                        $crawler->filter('.category-header.single-article-category a')->each(function($node) use(&$categories){
                                                $categories = $node->text() . ",";
                                        });*/
                                        $image='';
                                        $crawler->filter('.single-article-content article div img')->each(function($node) use(&$image){
                                                $image = $node->attr('src');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        if(empty($imageURL) && !empty($image_url))  {
                                            $imageURL=$image_url;
                                        }
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                              saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];                
                //echo $punchPage; exit;
                $crawler->filter('.listing-modern-grid article.listing-item')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('h2.title');
                        $postTitle = trim($title->text());
                        //echo $postTitle.'='.$link; exit;
                        $crawler = $client->request('GET',$link);
                        $dt = $crawler->filter(".single-post-meta time");
                        $date=$dt->attr("datetime");
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        if( $articleDay + 1 >= $currentDay){
                                $article = "";
                                $crawler->filter('.post.single-post-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                /*echo $date;
                                echo 'article='.$article; exit;*/
                                //check if string exists in the body 
                                //if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                        /*$categories = "";
                                        $crawler->filter('.post-header .term-badges a')->each(function($node) use(&$categories){
                                                $categories = $node->text();
                                        });*/
                                       $image='';
                                        $crawler->filter('.single-featured a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL;
                                        if(file_get_contents($imageURL)){
                                            saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                              saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeLeadership($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://leadership.ng/category/'.$newsCategory :'https://leadership.ng/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];                
                //echo $punchPage; exit;
                $crawler->filter('.jeg_main_content .jeg_posts .jeg_post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('.jeg_post_title');
                        $postTitle = $title->text();
                        $crawler = $client->request('GET',$link);
                        $dt=$crawler->filterXpath('//meta[@property="article:published_time"]')->attr('content');
                        $date=$dt;
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        //echo $postTitle.'='.$link.'='.$dt; exit;
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.content-inner  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                if(true) {
                                        /*$categories = "";
                                        $crawler->filter('.jeg_meta_category a')->each(function($node) use(&$categories){
                                                $categories = $node->text();
                                        });*/
                                        $image='';
                                        $crawler->filter('.jeg_featured.featured_image a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL;
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                                //$imager = $imaged[0].".png";
                                                //$imageURL = str_replace("').png","",strstr($imager,"h")); 
                                              saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeThenationonlineng($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://thenationonlineng.net/category/'.$newsCategory :'https://thenationonlineng.net/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];                
                //echo $punchPage; exit;
                $crawler->filter('.jeg_main_content .jeg_posts .jeg_post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('.jeg_post_title');
                        $postTitle = $title->text();
                        $crawler = $client->request('GET',$link);
                        $dt=$crawler->filterXpath('//meta[@property="article:published_time"]')->attr('content');
                        $date=$dt;
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        //echo $postTitle.'='.$link.'='.$dt; exit;
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.content-inner  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                /*echo $date;
                                echo 'article='.$article; exit;*/
                                //check if string exists in the body 
                                //if( strpos($article,"University") !== false || strpos($article,"Student") !== false || strpos($article,"Federal Government") !== false ){
                                if(true) {
                                        /*$categories = "";
                                        $cat=$crawler->filter('.jeg_meta_category a');
                                        $categories = $cat->text();*/
                                        $image='';
                                        $crawler->filter('.jeg_featured.featured_image a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL; exit;
                                        if(!empty($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeThisdaylive($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.thisdaylive.com/index.php/'.$newsCategory :'https://www.thisdaylive.com/index.php/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
               // echo $punchPage; exit;
                $crawler->filter('.td-module-thumb')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('a')->extract('title');
                        $postTitle = $title[0];
                        $crawler = $client->request('GET',$link);
                        $dt=$crawler->filter('.td-post-date time');
                        $date=$dt->attr("datetime");
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        //echo $postTitle.'='.$link.'='.$date; exit;
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.td-post-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                if(true) {
                                        /*$categories = "";
                                        $cat=$crawler->filter('.td-category li:last-child a');
                                        $categories = $cat->text();*/
                                        $image='';
                                        $crawler->filter('.td-post-featured-image a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL; exit;
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                             saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeTribuneonlineng($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://tribuneonlineng.com/'.$newsCategory :'https://tribuneonlineng.com/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
               // echo $punchPage; exit;
                $crawler->filter('.pt-cv-wrapper .pt-cv-ifield')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                        $links = $node->filter('a')->extract('href');
                        $link = $links[0];
                        $title = $node->filter('h4.pt-cv-title');
                        $postTitle = $title->text();
                        /*$categories = "";
                        $cat = $node->filter('.post-title');
                        $categories = $cat->text();*/
                        $crawler = $client->request('GET',$link);
                        $dt=$crawler->filter('span.time .post-published.updated');
                        $date=$dt->attr("datetime");
                        $datePublished[] = strtotime($date);
                        date_default_timezone_set('Africa/Lagos');
                        $articleTimestamp = strtotime($date);
                        $articleDay = date('z',$articleTimestamp);
                        $currentDay = date('z');
                        //echo $postTitle.'='.$link.'='.$date; exit;
                        if( $articleDay + 1 >= $currentDay  ){
                                $article = "";
                                $crawler->filter('.single-post-content  p')->each(function($node)use(&$article,&$articles){
                                        $article .= $node->html(); 
                                });
                                if(true) {
                                        /*$crawler->filter('.bf-breadcrumb-items li a span')->each(function($node) use(&$categories){
                                                $categories .= $node->text() . ",";
                                        });*/
                                        $image='';
                                        $crawler->filter('.single-featured a')->each(function($node) use(&$image){
                                                $image = $node->attr('href');
                                        });
                                         $imageURL = $image; 
                                        //save it to the database
                                        $date=date('Y-m-d H:i:s',$articleTimestamp);
                                        //echo $imageURL; exit;
                                        if(file_get_contents($imageURL)){
                                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                                        }else{
                                         saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
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
                            /*$categories = "";
                            $cat=$crawler->filter('.rtp-meta-cat a:first-child');
                            $categories = $cat->text();*/
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
                            }
                             
                    }
                }
                $i++;
                $crawler = $client->request('GET',$punchPage);
                //echo 'page='.$punchPage; exit;
                $crawler->filter('.rtp-listing-post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                $links=$node->filter('.rtp-listing-post .entry-title a')->extract('href');
               $link=$links[0];
               $title=$node->filter('.rtp-listing-post .entry-title a');
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
                            /*$categories = "";
                            $crawler->filter('.rtp-meta-cat a')->each(function($node) use(&$categories){
                                    $categories .= $node->text() . ",";
                            });*/
                            $image='';
                            $img=$crawler->filter('.entry-content p img')->extract('src');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapePremiumtimesng($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.premiumtimesng.com/category/'.$newsCategory :'https://www.premiumtimesng.com/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
               // echo $punchPage; exit;
               $crawler = $client->request('GET',$punchPage);
                //echo 'page='.$punchPage; exit;
                $crawler->filter('.jeg_postblock_content')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles,&$newsCategory){
               $links=$node->filter('.jeg_post_title a')->extract('href');
               $link=$links[0];
               $title=$node->filter('.jeg_post_title a');
               $postTitle = $title->text();
               /*$cat=$crawler->filter('.jeg_post_category a');
               $categories=$cat->text();*/
               $crawler = $client->request('GET',$link);
               $dt=$crawler->filter('.jeg_inner_content .jeg_meta_date a');
               $date=$dt->text();
               $datePublished[] = strtotime($date);
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $currentDay = date('z');
                $articleDay = date('z',$articleTimestamp);
               if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.content-inner p')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                    if(true) {
                            $image='';
                            $img=$crawler->filter('.jeg_featured a')->extract('href');
                            $imageURL=$img[0];
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                            saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                    saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeSaharareporters($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'http://saharareporters.com/'.$newsCategory :'http://saharareporters.com/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
               // echo $punchPage; exit;
               $crawler = $client->request('GET',$punchPage);
                //echo 'page='.$punchPage; exit;
               $crawler->filter('.view-content .block-module-content')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                $links=$node->filter('.block-module-content-header-title a')->extract('href');
               $link=$links[0];
               //print_r($links); exit;
               $title=$node->filter('.block-module-content-header-title a');
               $postTitle = $title->text();
               $categories = "";
               //echo 'http://saharareporters.com/'.$link; 
               /*$cat=$crawler->filter('.page-header-title');
               $categories=$cat->text();*/
               $crawler = $client->request('GET','http://saharareporters.com/'.$link);
               $title=$crawler->filter('.page-header-title');
               $postTitle = $title->text();
               $dt=$crawler->filter('.page-header-attribution-date');
               $date=$dt->text();
                $datePublished[] = strtotime($date);
            //echo 'dt='.$date.'='.$datePublished[0];
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $currentDay = date('z');
                $articleDay = date('z',$articleTimestamp);
                //echo $articleDay.'='.$currentDay; exit;
                //echo $postTitle.'='.$link.'='.$date; exit;
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.story-content p')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                //echo 'art='.$article; //exit;
                    if(true) {
                            $image='';
                            $img=$crawler->filter('.story-content .block-story-object-asset img')->extract('src');
                            //print_r($img);
                            $imageURL=$img[0];
                            //echo $imageURL; exit;
                            //save it to the database
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeSunnewsonline($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://www.sunnewsonline.com/category/'.$newsCategory :'https://www.sunnewsonline.com/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
                $crawler = $client->request('GET',$punchPage);
                $crawler->filter('.jeg_post')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles){
                $links=$node->filter('.jeg_postblock_content .jeg_post_title a')->extract('href');
               $link=$links[0];
               $title=$node->filter('.jeg_postblock_content .jeg_post_title a');
               $postTitle = $title->text();
               $crawler = $client->request('GET',$link);
               /*$cat=$crawler->filter('.jeg_meta_container .jeg_meta_category a');
               $categories=$cat->text();*/
               $dt=$crawler->filter('.jeg_meta_container .jeg_meta_date');
               $date=$dt->text();
                $datePublished[] = strtotime($date);
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $currentDay = date('z');
                $articleDay = date('z',$articleTimestamp);
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter('.content-inner p')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                    if(true) {
                            $image='';
                            $img=$crawler->filter('.jeg_featured.featured_image a')->extract('href');
                            $imageURL=$img[0];
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    function scrapeVenturesafrica($newsCategory,$numberOfPagesToScrape){
        $client = new Client();
        $newsCategory = strtolower($newsCategory);
        $i = 1;
        while($i < $numberOfPagesToScrape ){
                $punchPage = $i <= 1 ? 'https://venturesafrica.com/category/'.$newsCategory :'https://venturesafrica.com/category/'.$newsCategory.'/page/'.$i;
                $crawler = $client->request('GET',$punchPage);
                $articles = [];
                $datePublished = [];
                $relevantArticles = [];
                $j = 0;
                $categories="";
                $cat=explode('/',$newsCategory);
                $categories=$cat[0];
               $crawler = $client->request('GET',$punchPage);
               $crawler->filter('#top-stories .teaser-list li')->each(function ($node) use($crawler,$client,&$articles,&$datePublished,&$categories,$i,&$j,&$relevantArticles,&$newsCategory){
                $links=$node->filter('article a')->extract('href');
               $link=$links[0];
               $title=$node->filter('.text span strong');
               $postTitle = $title->text();
               $post_ids=$node->filter('article a')->extract('data-post-id');
               $post_id = '#post-'.$post_ids[0];
               //$categories = "";
               $crawler = $client->request('GET',$link);
               /*$cat=$crawler->filter('#main .inner .category-label');
               $categories=$cat->text();*/
               $dt=$crawler->filter($post_id.' .post-date');
               $date=$dt->text();
                $datePublished[] = strtotime($date);
                date_default_timezone_set('Africa/Lagos');
                $articleTimestamp = strtotime($date);
                $currentDay = date('z');
                $articleDay = date('z',$articleTimestamp);
                if( $articleDay + 1 >= $currentDay  ){
                $article = "";
                $crawler->filter($post_id.' .entry-content .post-content p')->each(function($node)use(&$article,&$articles){
                        $article .= $node->html();
                });
                if(true) {
                            $image='';
                            $img=$crawler->filter('.imgwrap img')->extract('src');
                            $imageURL=$img[0];
                            $date=date('Y-m-d H:i:s',$articleTimestamp);
                            if(file_get_contents($imageURL)){
                                saveInDatabase($postTitle,$article,$imageURL,$date,$categories,$link);   
                            }else{
                                saveInDatabase($postTitle,$article,'',$date,$categories,$link);   
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
    scrapePunch("news",5);
    scrapeGuardian("news/nigeria/national/",3);
    scrapeIndependent("business/abuja-business/",2,5);
    scrapeLeadership("business",2);
    scrapeThenationonlineng("news",2);
    scrapeThisdaylive("politics",2);
    scrapeTribuneonlineng("entertainment",2);
    scrapeVanguardngr("entertainment/",2);
    scrapePremiumtimesng("news/headlines",2);
    scrapeSaharareporters("politics",2);
    scrapeSunnewsonline("national",2);
    scrapeVenturesafrica("business",2);