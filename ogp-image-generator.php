<?php
/*
Plugin Name: ogp image generator
Description: OG画像自動生成プラグイン
Version: 1.0
*/

/*
Plugin Name: ogp image generator
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

define('ALLOW_UNFILTERED_UPLOADS', true);
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
            // add_action('save_post',         [$this, 'savepost_ogimage']);
            // add_action('save_post', 'savepost_ogimage');
        }

        if (function_exists('register_uninstall_hook'))
        {
            register_uninstall_hook(__FILE__, 'ogp_plugin_uninstall');
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

} // end of class

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

function savepost_ogimage($post_ID) {
  $ogp_font_url =     get_option('ogp_font_url', null);
  $ogp_font_size =     get_option('ogp_font_size', null);
  $ogp_font_color =     get_option('ogp_font_color', null);
  $ogp_font_color = str_replace('#', '',  $ogp_font_color);
  $ogp_new_line_char_length = get_option('ogp_new_line_char_length', null);
  $original_image_id =    get_option('original_image', null);
  $original_images = wp_get_attachment_image_src($original_image_id, 'full');
  $original_image = $original_images[0];
  $url = plugin_dir_url( __FILE__ ) . 'includes/generate.php?post_id=' . $post_ID . '&font_url=' . $ogp_font_url . '&original_image=' . $original_image . '&font_size=' . $ogp_font_size . '&font_color=' . $ogp_font_color . '&new_line_num=' . $ogp_new_line_char_length;

  file_get_contents($url);
}
add_action('save_post', 'savepost_ogimage');


function ogp_plugin_uninstall() {
  delete_option('ogp_font_url');
  delete_option('ogp_font_size');
  delete_option('ogp_font_color');
  delete_option('ogp_new_line_char_length');
  delete_option('original_image');
}




