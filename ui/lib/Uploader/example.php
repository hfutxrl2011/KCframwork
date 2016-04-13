<?php
require_once __DIR__ . '/UploadFile.class.php';

$tmp = new Uploader_UploadFile('http://139.196.180.9:8080/gips/index.php/upload');
var_dump($tmp->uploadFile("UploadFile.class.php"));
var_dump($tmp->getResDetail());
var_dump($tmp->getError());

$tmp = new Uploader_UploadFile('http://139.196.180.9:8080/gips/index.php/upload');
var_dump($tmp->uploadImg("UploadFile.class.php"));
var_dump($tmp->getResDetail());
?>
