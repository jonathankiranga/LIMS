<?php



class makebarcode {
    var $targetPath = "reports/";
    var $SaveImagepath='' ;
    var $barcode='';
    var $website;
        /*
         * $linear = array(
    'C128A'      => array('0123456789', 'CODE 128 A'),
    'C128B'      => array('0123456789', 'CODE 128 B'),
    'C128C'      => array('0123456789', 'CODE 128 C'),
    'C128'       => array('0123456789', 'CODE 128'),
    'C39E+'      => array('0123456789', 'CODE 39 EXTENDED + CHECKSUM'),
    'C39E'       => array('0123456789', 'CODE 39 EXTENDED'),
    'C39+'       => array('0123456789', 'CODE 39 + CHECKSUM'),
    'C39'        => array('0123456789', 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9'),
    'C93'        => array('0123456789', 'CODE 93 - USS-93'),
    'CODABAR'    => array('0123456789', 'CODABAR'),
    'CODE11'     => array('0123456789', 'CODE 11'),
    'EAN13'      => array('0123456789', 'EAN 13'),
    'EAN2'       => array('12',         'EAN 2-Digits UPC-Based Extension'),
    'EAN5'       => array('12345',      'EAN 5-Digits UPC-Based Extension'),
    'EAN8'       => array('1234567',    'EAN 8'),
    'I25+'       => array('0123456789', 'Interleaved 2 of 5 + CHECKSUM'),
    'I25'        => array('0123456789', 'Interleaved 2 of 5'),
    'IMB'        => array('01234567094987654321-01234567891', 'IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200'),
    'IMBPRE'     => array('AADTFFDFTDADTAADAATFDTDDAAADDTDTTDAFADADDDTFFFDDTTTADFAAADFTDAADA', 'IMB pre-processed'),
    'KIX'        => array('0123456789', 'KIX (Klant index - Customer index)'),
    'MSI+'       => array('0123456789', 'MSI + CHECKSUM (modulo 11)'),
    'MSI'        => array('0123456789', 'MSI (Variation of Plessey code)'),
    'PHARMA2T'   => array('0123456789', 'PHARMACODE TWO-TRACKS'),
    'PHARMA'     => array('0123456789', 'PHARMACODE'),
    'PLANET'     => array('0123456789', 'PLANET'),
    'POSTNET'    => array('0123456789', 'POSTNET'),
    'RMS4CC'     => array('0123456789', 'RMS4CC (Royal Mail 4-state Customer Bar Code)'),
    'S25+'       => array('0123456789', 'Standard 2 of 5 + CHECKSUM'),
    'S25'        => array('0123456789', 'Standard 2 of 5'),
    'UPCA'       => array('72527273070', 'UPC-A'),
    'UPCE'       => array('725277', 'UPC-E'),
);
         */
    
    function __construct() {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $websiteName = $scheme . "://" . $domainName;
        $this->website= $websiteName;
        
       $this->barcode = new \Com\Tecnick\Barcode\Barcode();
       if (! is_dir($this->targetPath)) {
            mkdir($this->targetPath, 0777, true);
        }
   }
     
    function Makebarcode($sampleid){
        
        $bobj = $this->barcode->getBarcodeObj('C128',$sampleid, -3, -30, 'black', [0, 0, 0, 0]);
        $imageData = $bobj->getPngData();
        
        $this->SaveImagepath = $this->targetPath . $sampleid . '.png';

       file_put_contents($this->SaveImagepath, $imageData);
       
       return $this->SaveImagepath;
    }
    
    function get($sampleid){
        $filename=$this->targetPath . $sampleid . '.png';
        if(is_file($filename)){
           $return = $filename;
        }else{
           $return = $this->Makebarcode($sampleid);
        }
        return $return;
    }
    
    
     function MakeQrcode($qr_code_text,$kra_invoice_no){
        
        $bobj = $this->barcode->getBarcodeObj('QRCODE,H',$qr_code_text, -4, -4, 'black', array(-2, -2, -2, -2))->setBackgroundColor('#f0f0f0');
        $imageData = $bobj->getPngData();
        
        $this->SaveImagepath = $this->targetPath .$kra_invoice_no. 'QRCODE.png';

       file_put_contents($this->SaveImagepath, $imageData);
       
       return $this->SaveImagepath;
    }
    
    function getQr($kra_invoice_no){
        $filename = $this->targetPath . $kra_invoice_no. 'QRCODE.png';
        if(is_file($filename)){
           $return = $filename;
        }else{
           $return = $this->targetPath . 'icons8-no-image-100.png';
        }
        return $return;
    }
    
     
}
