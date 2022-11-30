<?php
/*
Plugin Name: ogp image generator
Description: OG画像自動生成プラグイン
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit;

//  require_once("../../../wp-load.php");
define('ALLOW_UNFILTERED_UPLOADS', true);
add_action('init', 'OgpImageGenerator::init');

class OgpImageGenerator
{
    const VERSION           = '1.0.0';
    const PLUGIN_ID         = 'ogp-image-generator';
    const PLUGIN_MENU_SLUG      = self::PLUGIN_ID;
    const PLUGIN_FONT_URL       = 'ogp_font_url';
    const PLUGIN_ORIGINAL_IMAGE = 'original_image';

    static function init()
    {
        return new self();
    }

    function __construct()
    {
        if (is_admin() && is_user_logged_in()) {
            add_action('admin_menu',        [$this, 'set_ogp_menu']);
            add_filter('upload_mimes',      [$this, 'add_upload_mimes']);
        }
    }

    // ---------------------------------------
  // 設定画面
  // ---------------------------------------
  function show_config()
  {
      return include_once (__dir__.'/includes/admin-menu.php');
  }


  // ---------------------------------------
  // メニュー表示
  // ---------------------------------------
  function set_ogp_menu()
  {
      add_menu_page(
          'OG画像自動生成',
          'OG画像自動生成',
          'manage_options',
          self::PLUGIN_MENU_SLUG,
            [$this, 'show_config'],
          'dashicons-format-gallery',
          99
      );
  }

  // ---------------------------------------
  // アップロード時の拡張子を追加
  // ---------------------------------------
  function add_upload_mimes($existing_mimes = array())
  {
      $existing_mimes['ttf'] = 'application/octet-stream';
      $existing_mimes['otf'] = 'application/octet-stream';
      return $existing_mimes;
  }


  //全角・半角が混在する文字列でも文字数を指定して同じ長さで改行するための関数
  function mb_wordwrap( $string, $width = 35, $break = PHP_EOL ) {
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

  // $fontSize = 32; // 文字サイズ
  // $fontFamily = 'NotoSansCJKjp-Bold.ttf'; // 字体
  // $post_id = $_GET['post_id'];
  // $txt = mb_wordwrap(get_the_title($post_id), 11); //　テキスト
  // $slug = get_post($post_id)->post_name;
  // var_dump($slug);
  // $img = imagecreatefrompng('/Users/ryomaarimura/Local Sites/practicearimuraryomacom/app/public/wp-content/themes/hagukumu.co.jp/og/og_base.png'); // 背景画像
  // $color = imagecolorallocate($img, 255, 255, 255); // テキストの色指定(RGB)
  // $image_path = "/Users/ryomaarimura/Local Sites/practicearimuraryomacom/app/public/wp-content/themes/hagukumu.co.jp/og/img/ogp-$post_id.png";

  // $result = imagettfbbox( $fontSize, 0, $fontFamily, $txt); //テキストを縦横中央に配置するためテキスト全体の位置情報取得
  // $x0 = $result[6];
  // $y0 = $result[7];
  // // 右下
  // $x1 = $result[2];
  // $y1 = $result[3];
  // $width = $x1 - $x0;
  // $height = $y1 - $y0;
  // $x = ceil((1200 - $width) / 2); //1200は画像の幅
  // $y = ceil((630 - $height) / 2); //630は画像の縦

  // imagefttext($img, $fontSize, 0, $x, $y, $color, $fontFamily, $txt);

  // header('Content-Type: image/png');
  // imagepng($img, $image_path);
  // imagedestroy($img);

} // end of class





