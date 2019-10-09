<?php

// composer require phpseclib/phpseclib:~2.0
require_once __DIR__ . '/../vendor/autoload.php';

use phpseclib\Crypt\RSA;

$plaintext = json_encode([
    1, 3, 4
]);

$rsa = new RSA();
$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

// print_r($rsa->createKey(1024)); // to get private && private and save them in files
// die;

$rsa->loadKey(file_get_contents('public.key')); // private key
$ciphertext = base64_encode($rsa->encrypt($plaintext));
echo $ciphertext;

echo "\n";

$rsa->loadKey(file_get_contents('private.key')); // private key
echo $rsa->decrypt(base64_decode($ciphertext));
