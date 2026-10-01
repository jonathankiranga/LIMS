<?php
include('includes/PDFStarter.php');

class PDFslip{
        var $pdf,
        $slip2,
        $columRow,
        $space,
        $Page_Height,
        $Top_Margin,
        $Bottom_Margin,
        $line_height,
        $underline_header,
        $Left_Margin,
        $Page_Width,
        $Right_Margin,
        $colum1start,
        $colum2start,
        $colum1end,
        $colum2end;


function __construct() {
        global $pdf,
        $Page_Height,
        $Top_Margin,
        $Bottom_Margin,
        $line_height,
        $underline_header,
        $Left_Margin,
        $Page_Width,
        $Right_Margin;


    $this->pdf = $pdf ;
    $this->Page_Height = $Page_Height ;
    $this->Top_Margin  = $Top_Margin ;
    $this->line_height = $line_height ;
    $this->Left_Margin = $Left_Margin ;
    $this->Right_Margin = $Right_Margin ;
    $this->Page_Width = $Page_Width ;
    $this->Bottom_Margin = $Bottom_Margin;
    $this->space = 10;
}

function pgheader(){
global $PageNumber;

if ($PageNumber>1){ $this->pdf->newPage(); }

$FontSize = 10 ;
$YPos = $this->Page_Height - $this->Top_Margin ;
$XPos = $this->Left_Margin+0;
$line_height = $this->line_height;

// colum one
/* Prints company logo */
$this->pdf->addJpegFromFile($_SESSION['LogoFile'], $XPos+10, $YPos-20, 0,30);

/* Prints company info */
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*3),300,$FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*4),300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*5),300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*6),300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*7),300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*8),300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*9),300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);
//end of colum on begin  colum two

    /* Prints company logo */
$Y =(($this->Page_Width - $this->Left_Margin) * 0.5) - $this->space ;
$this->slip2 = $Y+50;

$this->pdf->addJpegFromFile($_SESSION['LogoFile'],$Y+($XPos+10),$YPos-20,0,30);
    /* Prints company info */
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*3),$Y+300,$FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*4),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*5),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*6),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*7),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*8),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$LeftOvers = $this->pdf->addTextWrap($Y+50,$YPos-($line_height*9),$Y+300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);

$this->underline_header = $YPos -($line_height * 9.5) ;
$this->colum1start = $this->Left_Margin ;
$this->colum1end   = (($this->Page_Width - $this->Left_Margin) * 0.5) ;

$this->colum2start = $this->slip2 ;// $this->colum1end + $this->space + 20 ;
$this->colum2end   = ($this->Page_Width - $this->Right_Margin);

$this->pdf->line($this->colum1start,$this->underline_header ,$this->colum1end,$this->underline_header,$style2);
$this->pdf->line($this->colum2start,$this->underline_header ,$this->colum2end,$this->underline_header,$style2);

/// end of colum two
}

Function pgfooter($YPos){
    
    $messageArray=array();
    $messageArray[1][]='Be a Committed WASREB Employee';
    $messageArray[1][]='Be a Proud Kenyan';
    
    $messageArray[2][]='We are One Country';
    $messageArray[2][]='We are One Nation';
    $messageArray[2][]='Play Your Part in Promoting National Unity';
    
    $messageArray[3][]='Have you participated in Making WASREB,';
    $messageArray[3][]='a better organization?';
    $messageArray[3][]='Earn your pay today';
    
    
    $msgid = rand(1,3);
    $textmsg = $messageArray[$msgid];
        
    $FontSize = 10 ;
    $XPos = $this->Left_Margin+0;
    $line_height = $this->line_height;
    $LastRow = $this->Bottom_Margin ;
    // colum one
    /* Prints company logo */
    $lastposition=$this->Page_Width - $this->colum2start;
    /* Prints company info */
    $lineIndex= $YPos-($line_height*2);
    foreach ($textmsg as $value) {
        $LeftOvers = $this->pdf->addTextWrap(50,$lineIndex,300,$FontSize,$value);
        $lineIndex -= $line_height;
    }
    
//    $this->pdf->addJpegFromFile($_SESSION['rightfooterFile'], $XPos+20,$LastRow, 0,30);
 //   $this->pdf->addJpegFromFile($_SESSION['leftfooterFile'], $lastposition,$LastRow, 0,30);


    
    $msgid = rand(1,3);
    $textmsg =$messageArray[$msgid];
    //end of colum on begin  colum two
    /* Prints company logo */

    $Y =(($this->Page_Width - $this->Left_Margin) * 0.5) - $this->space ;
    $this->slip2 = $Y+50;

        /* Prints company info */
    
    $lineIndex= $YPos-($line_height*2);
    foreach ($textmsg as $value) {
        $LeftOvers = $this->pdf->addTextWrap($Y+50,$lineIndex,300,$FontSize,$value);
        $lineIndex -= $line_height;
    }
    
 //   $this->pdf->addJpegFromFile($_SESSION['rightfooterFile'],$Y+($XPos+20),$LastRow,0,30);
  //  $this->pdf->addJpegFromFile($_SESSION['leftfooterFile'],$Y+$lastposition,$LastRow,0,30);

}


function putline1($y){
    $this->pdf->line($this->colum1start,$y,$this->colum1end,$y);
}

function putline2($y){
    $this->pdf->line($this->colum2start,$y,$this->colum2end,$y);
}



}


class ONEPDFslip{
        var $pdf,
        $slip2,
        $columRow,
        $space,
        $Page_Height,
        $Top_Margin,
        $Bottom_Margin,
        $line_height,
        $underline_header,
        $Left_Margin,
        $Page_Width,
        $Right_Margin,
        $colum1start,
        $colum2start,
        $colum1end,
        $colum2end;


function __construct() {
        global $pdf,
        $Page_Height,
        $Top_Margin,
        $Bottom_Margin,
        $line_height,
        $underline_header,
        $Left_Margin,
        $Page_Width,
        $Right_Margin;


    $this->pdf = $pdf ;
    $this->Page_Height = $Page_Height ;
    $this->Top_Margin  = $Top_Margin ;
    $this->line_height = $line_height ;
    $this->Left_Margin = $Left_Margin ;
    $this->Right_Margin = $Right_Margin ;
    $this->Page_Width = $Page_Width ;
    $this->Bottom_Margin = $Bottom_Margin;
    $this->space = 10;
}

function pgheader(){
global $PageNumber;

if ($PageNumber>1){ $this->pdf->newPage(); }

$FontSize = 10 ;
$YPos = $this->Page_Height - $this->Top_Margin ;
$XPos = $this->Left_Margin+0;
$line_height = $this->line_height;

// colum one
/* Prints company logo */
$this->pdf->addJpegFromFile($_SESSION['LogoFile'], $XPos+10, $YPos-20, 0,30);

/* Prints company info */
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*3),300,$FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*4),300,$FontSize,$_SESSION['CompanyRecord']['regoffice1']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*5),300,$FontSize,$_SESSION['CompanyRecord']['regoffice2']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*6),300,$FontSize,$_SESSION['CompanyRecord']['regoffice3']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*7),300,$FontSize,$_SESSION['CompanyRecord']['regoffice4']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*8),300,$FontSize,$_SESSION['CompanyRecord']['regoffice5']);
$LeftOvers = $this->pdf->addTextWrap(50,$YPos-($line_height*9),300,$FontSize,$_SESSION['CompanyRecord']['regoffice6']);
//end of colum on begin  colum two

$this->underline_header = $YPos -($line_height * 9.5) ;
$this->colum1start = $this->Left_Margin ;
$this->colum1end   = (($this->Page_Width - $this->Left_Margin) * 0.5) ;
$this->pdf->line($this->colum1start,$this->underline_header ,$this->colum1end,$this->underline_header,$style2);

}

Function pgfooter($YPos){
    
    $messageArray=array();
    $messageArray[1][]='Be a Committed WASREB Employee';
    $messageArray[1][]='Be a Proud Kenyan';
    
    $messageArray[2][]='We are One Country';
    $messageArray[2][]='We are One Nation';
    $messageArray[2][]='Play Your Part in Promoting National Unity';
    
    $messageArray[3][]='Have you participated in Making WASREB,';
    $messageArray[3][]='a better organization?';
    $messageArray[3][]='Earn your pay today';
    
    
    $msgid = rand(1,3);
    $textmsg = $messageArray[$msgid];
        
    $FontSize = 10 ;
    $XPos = $this->Left_Margin+0;
    $line_height = $this->line_height;
    $LastRow = $this->Bottom_Margin ;
    // colum one
    /* Prints company logo */
    $lastposition=$this->Page_Width - $this->colum2start;
    /* Prints company info */
    $lineIndex= $YPos-($line_height*2);
    foreach ($textmsg as $value) {
        $LeftOvers = $this->pdf->addTextWrap(50,$lineIndex,300,$FontSize,$value);
        $lineIndex -= $line_height;
    }
    
//    $this->pdf->addJpegFromFile($_SESSION['rightfooterFile'], $XPos+20,$LastRow, 0,30);
 //   $this->pdf->addJpegFromFile($_SESSION['leftfooterFile'], $lastposition,$LastRow, 0,30);


    
}


function putline1($y){
    $this->pdf->line($this->colum1start,$y,$this->colum1end,$y);
}




}



?>
