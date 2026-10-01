<?php

Class Encryptme{

  const METHOD = 'aes-256-cbc';
  

  public static function encrypt($message) {
        $key = hex2bin('000102030405060708090a0b0c0d0e0f');
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);
        
        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $iv . $ciphertext;
    }

    public static function decrypt($message){
        $key = hex2bin('000102030405060708090a0b0c0d0e0f');
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($message, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($message, $ivsize, null, '8bit');
        
        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}



?>