<?php

function php_anchor($href,$caption){
    Global $RootPath,$Theme;
    
    echo '<p><img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" '
            . 'title="' . $caption. '" alt="" />' .
            ' <a href="'.$href.'">'.$caption.'</a>'
            . '</p>';

}

?>