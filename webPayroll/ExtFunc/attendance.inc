<?php

class attendance {


    function connect($address="192.168.1.1",$port=4370) {

       $mysock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

       socket_bind($mysock,$address,$port) or die('Could not bind to address');

       socket_listen($mysock, 5);

       $client = socket_accept($mysock);
       // read 1024 bytes from client
       $input = socket_read($client, 1024);
       // write received gprs data to the file
       $this->writeToFile('socketLog.txt', $input);

       socket_close($client);
       socket_close($mysock);
    }


    function writeToFile($strFilename, $strText) {
        if($fp = @fopen($strFilename,"w")) {
            $contents = fwrite($fp, $strText);
            
            fclose($fp);

            return true;
        } else {
            return false;
        }
    }



}
?>
