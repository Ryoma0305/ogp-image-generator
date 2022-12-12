<?php
// if ( ! defined( 'ABSPATH' ) ) {
// 	exit; // Exit if accessed directly
// }
require_once("../../../../wp-load.php");

$font_size = $_GET['font_size']; // 文字サイズ
$file_path = __FILE__;
$file_path1 = strstr(__FILE__, '/wp-content', true);
$font_url = $_GET['font_url']; // 字体
$font_start = strrpos($font_url, '/wp-content');
$font_end = strlen($font_url);
$font_file = substr($font_url, $font_start, $font_end);
$font_file_path = $file_path1 . $font_file; // フォントファイルパス
$ogp_new_line_char_length = $_GET['new_line_num']; //改行する文字数
$post_id = $_GET['post_id']; // post_id
$txt = mb_wordwrap(get_the_title($post_id), $ogp_new_line_char_length); //　テキスト
$slug = get_post($post_id)->post_name; // スラッグ

$original_img_url = $_GET['original_image']; // 背景画像URL
$original_img_start = strrpos($original_img_url, '/wp-content');
$original_img_end = strlen($original_img_url);
$original_img = substr($original_img_url, $original_img_start, $original_img_end);
$img_file_path = $file_path1 . $original_img; // 背景画像パス

$img_type = exif_imagetype($img_file_path);
if($img_type == 2){
    $img = imagecreatefromjpeg($img_file_path);
}elseif($img_type == 3){
    $img = imagecreatefrompng($img_file_path);
}
$hex_color = $_GET['font_color'];
$code_red = hexdec(substr($hex_color, 0, 2));
$code_green = hexdec(substr($hex_color, 2, 2));
$code_blue = hexdec(substr($hex_color, 4, 2));
$color = imagecolorallocate($img, $code_red, $code_green, $code_blue); // テキストの色指定(RGB)
$image_path = strstr(__FILE__, 'includes/generate.php', true) . "img/ogp-$post_id.png";
$img_result = getimagesize($img_file_path);
$result = imagettfbbox( $font_size, 0, $font_file_path, $txt); //テキストを縦横中央に配置するためテキスト全体の位置情報取得
$x0 = $result[6];
$y0 = $result[7];

$x1 = $result[2];
$y1 = $result[3];
$width = $x1 - $x0;
$height = $y1 - $y0;
$img_width = $img_result[0];
$ime_height = $img_result[1];
$x = ceil(($img_width - $width) / 2);
$y = ceil(($ime_height - $height) / 2);

imagefttext($img, $font_size, 0, $x, $y, $color, $font_file_path, $txt);

header('Content-Type: image/png');
imagepng($img, $image_path);
imagedestroy($img);
