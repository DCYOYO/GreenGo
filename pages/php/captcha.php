<?php
@header("Content-type: image/png");
srand((float)microtime() * 1000000);// 隨機種子
$im = imagecreate(100, 40);// 建立圖片
$black = ImageColorAllocate($im, 0, 0, 0);// 黑色
$white = ImageColorAllocate($im, 255, 255, 255);// 白色
$gray = ImageColorAllocate($im, 200, 200, 200);// 灰色
imagefill($im, 70, 25, $gray);// 填充背景色
$AuthResult = "";
for ($i = 0; $i < 5; $i++) {
    $AuthResult .= dechex(rand(1, 15));// 生成隨機數字
}
session_start();
$_SESSION["_authnum"] = md5($AuthResult);
imagestring($im, 100, 20, 15, $AuthResult, $black);
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($im, rand() % 100, rand() % 40, $black);// 在圖片上隨機生成點
}
ImagePNG($im);// 輸出圖片
ImageDestroy($im);// 釋放圖片資源
