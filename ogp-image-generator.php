<?php
/*
Plugin Name: OGP Image Generator
Description: OG画像自動生成プラグイン
Version: 1.0
Author: Arimura Ryoma
License: GPL2
*/

/*  Copyright 2022/12/09 Arimura Ryoma (email : ryomaaa@gmail.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) exit;
add_action('init', 'OgpImageGenerator::init');

class OgpImageGenerator
{
    const VERSION           = '1.0.0';
    const PLUGIN_ID         = 'ogp-image-generator';
    const PLUGIN_MENU_SLUG      = self::PLUGIN_ID;
    const PLUGIN_FONT_URL       = 'ogp_font_url';
    const PLUGIN_FONT_SIZE       = 'ogp_font_size';
    const PLUGIN_FONT_COLOR       = 'ogp_font_color';
    const PLUGIN_NEWLINE_CHAR_LENGTH      = 'ogp_new_line_char_length';
    const PLUGIN_ORIGINAL_IMAGE_ID = 'original_image';
    const PLUGIN_PREVIEW_IMAGE_URL = 'preview_image_url';
    const PLUGIN_PREVIEW_SAMPLE_TEXT = 'preview_sample_text';

    static function init()
    {
        return new self();
    }

    function __construct()
    {

      $oig_img_folder_path = plugin_dir_path( __FILE__ ) . 'img';
      $oig_preview_img_file_path = plugin_dir_path( __FILE__ ) . 'includes/preview';
      chmod($oig_img_folder_path, 0755);
      chmod($oig_preview_img_file_path, 0755);

        if (is_admin() && is_user_logged_in()) {
            add_action('admin_menu',        [$this, 'oig_set_ogp_menu']);
            add_filter('upload_mimes',      [$this, 'oig_add_upload_mimes']);
            add_filter( 'wp_check_filetype_and_ext', [$this, 'oig_add_allow_upload_extension_exception'],10,5);
            add_action('save_post', 'oig_savepost_ogimage');

            if ( current_user_can('contributor') && !current_user_can('upload_files') ){
              add_action('admin_init', [$this,'oig_allow_contributor_uploads']);
            }
        }

        if (function_exists('register_uninstall_hook'))
        {
            register_uninstall_hook(__FILE__, 'oig_plugin_uninstall');
        }
    }

    // ---------------------------------------
  // 設定画面
  // ---------------------------------------
  function oig_show_config()
  {
      return include_once (__dir__.'/includes/admin-menu.php');
  }


  // ---------------------------------------
  // メニュー表示
  // ---------------------------------------
  function oig_set_ogp_menu()
  {
      add_menu_page(
        'OGP Image Generator',
        'OGP Image Generator',
        'manage_options',
        self::PLUGIN_MENU_SLUG,
          [$this, 'oig_show_config'],
        'dashicons-format-gallery',
        100
      );
  }

  function oig_add_upload_mimes($mimes)
  {
      $mimes['ttf'] = 'application/x-font-ttf';
      return $mimes;
  }

  function oig_add_allow_upload_extension_exception( $data, $file, $filename,$mimes,$real_mime=null){
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if($ext == 'ttf'){
      return ['ext' => 'ttf', 'type' => 'application/x-font-ttf', 'proper_filename' => false];
    }else {
      return $data;
    }
  }

  // ---------------------------------------
  // 寄稿者にアップロード権限付与
  // ---------------------------------------
  function oig_allow_contributor_uploads()
  {
      $contributor = get_role('contributor');
      $contributor->add_cap('upload_files');
  }

  static function oig_generate_preview_image(){
    $file_path_to_public = strstr(__FILE__, '/wp-content', true);
    $file_path_to_wpload = $file_path_to_public . '/wp-load.php';
    require_once($file_path_to_wpload);

    $font_size = sanitize_text_field(get_option('ogp_font_size', null)); // 文字サイズ
    $file_path = __FILE__;

    $font_url = sanitize_url(get_option('ogp_font_url', null)); // 字体
    $font_start = strrpos($font_url, '/wp-content');
    $font_end = strlen($font_url);
    $font_file = substr($font_url, $font_start, $font_end);
    $font_file_path = $file_path_to_public . $font_file; // フォントファイルパス

    $ogp_new_line_char_length = sanitize_text_field(get_option('ogp_new_line_char_length', null)); //改行する文字数

    $default_text = 'サンプルテキストです。サンプルテキストです。サンプルテキストです。';
    $txt = oig_mb_wordwrap(get_option('preview_sample_text', $default_text), $ogp_new_line_char_length); //　テキスト

    $original_img_id = get_option('original_image', null); // 背景画像URL
    $original_img_url = wp_get_attachment_image_src($original_img_id, 'full')[0];
    $original_img_start = strrpos($original_img_url, '/wp-content');
    $original_img_end = strlen($original_img_url);
    $original_img = substr($original_img_url, $original_img_start, $original_img_end);
    $img_file_path = $file_path_to_public . $original_img; // 背景画像パス

    $img_type = exif_imagetype($img_file_path);
    if($img_type == 2){
        $img = imagecreatefromjpeg($img_file_path);
    }elseif($img_type == 3){
        $img = imagecreatefrompng($img_file_path);
    }

    $hex_color = get_option('ogp_font_color', null);
    $code_red = hexdec(substr($hex_color, 1, 2));
    $code_green = hexdec(substr($hex_color, 3, 2));
    $code_blue = hexdec(substr($hex_color, 5, 2));
    $color = imagecolorallocate($img, $code_red, $code_green, $code_blue); // テキストの色指定(RGB)
    $image_path = WP_PLUGIN_DIR . '/ogp-image-generator/img/preview/ogp-example.jpg';
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

    // $base64_img =  base64_encode($img);
    // return $base64_img;
  }
} // end of class

//全角・半角が混在する文字列でも文字数を指定して同じ長さで改行するための関数
function oig_mb_wordwrap( $string, $width = 35, $break = PHP_EOL ) {
  $one_char_array   = mb_str_split( $string );
  $char_point_array = array_map(
    function( $char ) {
      $point = 1; // 全角
      if ( strlen( $char ) === mb_strlen( $char ) ) { // 半角
        if ( ctype_upper( $char ) ) { // アルファベット大文字
          $point = 0.7; // 全角を基準とした大きさ
        } else { // アルファベット小文字 or 記号
          $point = 0.5; // 全角を基準とした大きさ
        }
      }

      return $point;
    },
    $one_char_array
  );

  $words_array = array();
  $point_sum   = 0;
  $start       = 0;
  foreach ( $char_point_array as $index => $point ) {
    $point_sum += $point;
    if ( $point_sum >= $width ) {
      $words_array[] = mb_substr( $string, $start, $index - $start );
      $start         = $index;
      $point_sum     = 0;
    }

    if ( $index === array_key_last( $char_point_array ) ) {
      $words_array[] = mb_substr( $string, $start, count( $one_char_array ) - $start );
    }
  }

  return implode( $break, $words_array );
}

function oig_savepost_ogimage($post_ID) {

  $file_path_to_public = strstr(__FILE__, '/wp-content', true);
  $file_path_to_wpload = $file_path_to_public . '/wp-load.php';
  require_once($file_path_to_wpload);

  $font_size = get_option('ogp_font_size', null); // 文字サイズ
  $file_path = __FILE__;

  $font_url = get_option('ogp_font_url', null); // 字体
  $font_start = strrpos($font_url, '/wp-content');
  $font_end = strlen($font_url);
  $font_file = substr($font_url, $font_start, $font_end);
  $font_file_path = $file_path_to_public . $font_file; // フォントファイルパス

  $ogp_new_line_char_length = get_option('ogp_new_line_char_length', null); //改行する文字数

  $txt = oig_mb_wordwrap(get_the_title($post_ID), $ogp_new_line_char_length); //　テキスト

  $original_img_id = get_option('original_image', null); // 背景画像URL
  $original_img_url = wp_get_attachment_image_src($original_img_id, 'full')[0];
  $original_img_start = strrpos($original_img_url, '/wp-content');
  $original_img_end = strlen($original_img_url);
  $original_img = substr($original_img_url, $original_img_start, $original_img_end);
  $img_file_path = $file_path_to_public . $original_img; // 背景画像パス

  $img_type = exif_imagetype($img_file_path);
  if($img_type == 2){
      $img = imagecreatefromjpeg($img_file_path);
  }elseif($img_type == 3){
      $img = imagecreatefrompng($img_file_path);
  }
  $image_path = strstr(__FILE__, 'ogp-image-generator.php', true) . "img/ogp-$post_ID.jpg";

  $hex_color = get_option('ogp_font_color', null);
  $code_red = hexdec(substr($hex_color, 1, 2));
  $code_green = hexdec(substr($hex_color, 3, 2));
  $code_blue = hexdec(substr($hex_color, 5, 2));
  $color = imagecolorallocate($img, $code_red, $code_green, $code_blue); // テキストの色指定(RGB)
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

}

function oig_plugin_uninstall() {
  delete_option('ogp_font_url');
  delete_option('ogp_font_size');
  delete_option('ogp_font_color');
  delete_option('ogp_new_line_char_length');
  delete_option('original_image');
}




