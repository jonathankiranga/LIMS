<?php
$PageSecurity=0;
include('includes/session.inc');

$imageDirectory = "repository";
$images = glob($imageDirectory."/*.{jpeg,jpg,png,gif}", GLOB_BRACE);
$FILES  = getiamgearray();
$count  = 0;
echo '<div class="container">'
      . '<div class="responsive">';
    foreach ($images as $index => $image) {
        if (array_search($image,$FILES,TRUE)===False) {
        $count++;
        echo '<div class="image-item" data-index="' . $index . '">';
        echo '<img src="' . $image . '" alt="No Unused Image"  height="200">';
        echo '</div>';
        }
    }
    if($count==0){
        
        echo '<p>No Images</p>';
        echo '<p>All Images have been assigned to documents</p>';
        
    }

  echo '</div>'
. '</div>';
 
function getiamgearray(){
    global $db;
      $images =array();
      
      $SQL="SELECT `picture` FROM `SalesHeader` where `picture` is not null";
      $Result=DB_query($SQL,$db);
      while($myrow = DB_fetch_array($Result)){
      $urlString = $myrow['picture'];
       $urls = json_decode($urlString,TRUE);
       
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = urldecode($url); // Decode the URL-encoded characters
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace(']', '' ,$validUrl);
                 $validUrl = str_replace('&quot;', '' ,$validUrl);
                 $answer   = explode("repository", $validUrl);
                 $validUrl = str_replace($answer[0],'',$validUrl);
                 $images[] = $validUrl ;
            }
        } 
      }
      
  return $images;
}

