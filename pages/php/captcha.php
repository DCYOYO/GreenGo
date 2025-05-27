<?php
@header("Content-type: image/png");
srand((float)microtime() * 1000000);
$im = imagecreate(58, 25);
$black = ImageColorAllocate($im, 0, 0, 0);
$white = ImageColorAllocate($im, 255, 255, 255);
$gray = ImageColorAllocate($im, 200, 200, 200);
imagefill($im, 48, 20, $gray);
$AuthResult = "";
for ($i = 0; $i < 5; $i++) {
    $AuthResult .= dechex(rand(1, 15));
}
session_start();
$_SESSION["_authnum"] = md5($AuthResult);
imagestring($im, 5, 10, 8, $AuthResult, $black);
for ($i = 0; $i < 50; $i++) {
    imagesetpixel($im, rand() % 70, rand() % 30, $black);
}
ImagePNG($im);
ImageDestroy($im);
