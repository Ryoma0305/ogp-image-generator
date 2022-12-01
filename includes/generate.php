<?php
// if ( ! defined( 'ABSPATH' ) ) {
// 	exit; // Exit if accessed directly
// }

require_once("../../../../wp-load.php");

$fontSize = 32; // 文字サイズ
$fontFamily = $ogp_font_url; // 字体
$post_id = $_GET['post_id'];
$txt = mb_wordwrap(get_the_title($post_id), 11); //　テキスト

$file_path =  __DIR__ . '/test.log';
$data = $fontFamily;
file_put_contents($file_path, print_r($data, true));

$slug = get_post($post_id)->post_name;
$img = imagecreatefrompng('/Users/ryomaarimura/Local Sites/practicearimuraryomacom/app/public/wp-content//plugins/ogp-image-generator/og_base.png'); // 背景画像
$color = imagecolorallocate($img, 255, 255, 255); // テキストの色指定(RGB)
$image_path = "/Users/ryomaarimura/Local Sites/practicearimuraryomacom/app/public/wp-content/plugins/ogp-image-generator/img/ogp-$post_id.png";

$result = imagettfbbox( $fontSize, 0, $fontFamily, $txt); //テキストを縦横中央に配置するためテキスト全体の位置情報取得
$x0 = $result[6];
$y0 = $result[7];
// 右下
$x1 = $result[2];
$y1 = $result[3];
$width = $x1 - $x0;
$height = $y1 - $y0;
$x = ceil((1200 - $width) / 2); //1200は画像の幅
$y = ceil((630 - $height) / 2); //630は画像の縦

imagefttext($img, $fontSize, 0, $x, $y, $color, $fontFamily, $txt);

header('Content-Type: image/png');
imagepng($img, $image_path);
imagedestroy($img);
