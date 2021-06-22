<?php
header("Content-Type: text/html;charset=utf-8");
ini_set("display_errors",1);
//error_reporting(E_);
require "vendor/autoload.php";
//the task is to check the homepage for three words . University , Federal Goverment and Student , Anytime we see any of these words , we click the article and then fetch the article's title , the article , the date ,the image URL ,the category
use Goutte\Client;
    function saveInDatabase($url,$tag_list,$data){
        $host = "localhost";
        $dbName = "ticket_search";
        $username = "test_user";
        $password = "nSaL#Q;[(y]1";
        $db = new PDO("mysql:host=$host;dbname=$dbName",$username,$password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // $time = date('Y-m-d H:i:s');
        $preQuery = "SET NAMES 'utf8'";
        $preparatory = $db->prepare($preQuery);
        $preparatory->execute();
        $sql = "INSERT INTO page_data (`url`,`tag_list`,`data`,`timestamp`) VALUES (?,?,?,?) ";
        $prepared = $db->prepare($sql);
        $executed = $prepared->execute([$url,implode(',',$tag_list),json_encode($data),date('Y-m-d H:i:s')]);
        if($executed){
            return true;
        }        
    }
    function scrapePage($url,$keywords,$dt){
        $client = new Client();
        $punchPage = $url;
        $crawler = $client->request('GET',$punchPage);   
        $data=array();     
        $last_date=$ldate=null;        
        $crawler->filter('body .news_table tr:not(.dateDivisionRow)')->each(function ($node) use($crawler,$client,&$data,&$last_date,&$ldate,&$keywords){
            $txt=$node->filter('div.story_header>a>span')->extract('_text');
            $text=$txt[0];
            $ldate=($node->attr("data-datenews"));
            //echo 'match '.$keywords.' in '.$text.' '.preg_match("/$keywords/",$text);
            if(preg_match("/$keywords/i",$text)) {
                //echo 'matched found';
                $ticker=$node->filter('.ticker.fpo_overlay')->extract('data-ticker');
                $link=$node->filter('.newsTitleLink')->extract("href");
                $data[]=$ldate.' '.$ticker[0].' '.$link[0];
            }
            $last_date=$node->attr("data-unlockdateutc");
        });
        $i=0;
        //echo $dt.'=='.$ldate;
        while($dt<=$ldate) {
            $crawler=$client->request('GET','https://thefly.com/ajax/newsAjax.php?market_stories=on&hot_stocks_filter=on&rumors_filter=on&general_news_filter=on&periodicals_filter=on&earnings_filter=on&technical_analysis_filter=on&options_filter=on&syndicates_filter=on&onthefly=on&insight_filter=on&market_mover_filter=on&analyst_recommendations=on&upgrade_filter=on&downgrade_filter=on&initiate_filter=on&no_change_filter=on&events=on&symbol=&page=news&allDay=0&_='.$last_date);
            $crawler->filter('body .news_table tr:not(.dateDivisionRow)')->each(function ($node) use($crawler,$client,&$data,&$i,&$ldate,&$dt,&$last_date,&$keywords){                
                //$data[]=$i.':-'.$dt.'=='.$last_date.($ldate<=$dt).'==='.$node->attr("data-datanews").$node->text();
                $txt=$node->filter('div.story_header>a>span')->extract('_text');
                $text=$txt[0];
                $ldate=($node->attr("data-datenews"));
                if(preg_match("/$keywords/i",$text)) {
                    $ticker=$node->filter('.ticker.fpo_overlay')->extract('_text');
                    $link=$node->filter('.newsTitleLink')->extract("href");
                    $data[]=$ldate.' '.$ticker[0].' '.$link[0];
                }
                $last_date=$node->attr("data-unlockdateutc");
                $ldate=($node->attr("data-datenews"));
                //$i++;
                //echo 
            });
            //echo $dt.'=='.$ldate."-".($ldate<=$dt).'-'.($ldate>=$dt); 
            //if($i>4) break;
        }
        //saveInDatabase($url,$tag_list,$data);
        return $data;
    }    
    $url=$_POST['url'];
    $keywords=$_POST['keywords'];
    $dt1=$_POST['date'];
    //$dt=strtotime($dt1.' 12:00:00');
    $dt=$dt1.' 00:00:00';
    //$dt2=DateTime::createFromFormat('Y-m-d H:i:s',$dt);
    $type=isset($_POST['type']) ? $_POST['type'] : '';
    $resp['message']=scrapePage($url,$keywords,($dt));
    $resp['success']=true;
    $resp['success_msg']='Data fetched successfully';
    echo json_encode($resp);
    exit;
    