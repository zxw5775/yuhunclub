<?php
include('SimpleCaptcha.php');
session_start();

$captcha = new SimpleCaptcha();
$captcha->width = 140;
$captcha->height = 60;
$captcha->scale = 3;
$captcha->blur = true;

// OPTIONAL Change configuration...
//$captcha->wordsFile = 'words/es.php';
//$captcha->session_var = 'secretword';
//$captcha->imageFormat = 'png';
//$captcha->resourcesPath = "/var/cool-php-captcha/resources";

// Image generation
$text = $captcha->CreateImage();



