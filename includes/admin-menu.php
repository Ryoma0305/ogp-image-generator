<?php
// ---------------------------------------
// 設定画面
// ---------------------------------------
// function show_config()
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_enqueue_media();

if( isset($_POST['original_image']) && isset($_POST['ogp_font_url']) && isset($_POST['ogp_font_size'])){
    check_admin_referer('ogp_config');
    update_option(self::PLUGIN_FONT_URL, $_POST['ogp_font_url']);
    update_option(self::PLUGIN_FONT_SIZE, $_POST['ogp_font_size']);
    update_option(self::PLUGIN_FONT_COLOR, $_POST['ogp_font_color']);
    update_option(self::PLUGIN_NEWLINE_CHAR_LENGTH, $_POST['ogp_new_line_char_length']);
    update_option(self::PLUGIN_ORIGINAL_IMAGE, $_POST['original_image']);
}

if(isset($_POST['preview'])){
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

        $txt = oig_mb_wordwrap('サンプルテキストです。サンプルテキストです。サンプルテキストです。', $ogp_new_line_char_length); //　テキスト

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
        // $image_path = strstr(__FILE__, 'ogp-image-generator.php', true) . "img/preview/ogp-example.png";
        $image_path = WP_PLUGIN_DIR . '/ogp-image-generator/img/preview/ogp-example.png';

        $img_result = getimagesize($img_file_path);

        $file_path_to_log =  __DIR__ . '/preview.log';
        $data = array('file_path_to_wpload' => $file_path_to_wpload, 'font_url' => $font_url, 'font_file' =>  $font_file, 'file_path_to_public' => $file_path_to_public, 'font_file_path' =>$font_file_path, 'original_img_url' => $original_img_url, 'original_img_start' => $original_img_start, 'original_img_end' => $original_img_end, 'original_img' => $original_img, 'img_file_path' =>$img_file_path, 'image_path' => $image_path,);
        file_put_contents($file_path_to_log, print_r($data, true));

        $result = imagettfbbox( $font_size, 0, $font_file_path, $txt); //テキストを縦横中央に配置するためテキスト全体の位置情報取得

        $file_path_to_log =  __DIR__ . '/preview2.log';
        $data = array('hex_color' => $hex_color, 'code_red' => $code_red, 'code_green' => $code_green, 'code_blue' => $code_blue, 'color' => $color);
        file_put_contents($file_path_to_log, print_r($data, true));

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
?>

<div class="wrap">
    <div id="icon-options-general"><br /></div><h2>OGP Image Generator Settings</h2>
        <form action="" method="post">
<?php
wp_nonce_field('ogp_config');
$ogp_font_url =     get_option(self::PLUGIN_FONT_URL, null);
$ogp_font_size =     get_option(self::PLUGIN_FONT_SIZE, null);
$ogp_font_color =     get_option(self::PLUGIN_FONT_COLOR, '#000');
$ogp_new_line_char_length = get_option(self::PLUGIN_NEWLINE_CHAR_LENGTH, null);

$original_image =    get_option(self::PLUGIN_ORIGINAL_IMAGE, null);
$ogp_image_urls = [];
$titles = [];
$ids = explode(',', $original_image);
foreach($ids as $id){
    $image = wp_get_attachment_image_src($id, 'full');
    if( $image !==  false ){
        $ogp_image_urls[] = $image[0];
        $post = get_post($id);
        $titles[] = $post->post_title;
    }
}

$file = __FILE__;
$file_path = strstr(__FILE__, 'includes', true);
$file_path_to_img = $file_path . 'img/';

$preview_image_path = WP_PLUGIN_URL . '/ogp-image-generator/img/preview/ogp-example.png';
?>
            <div class="form-table-wrapper" style="padding-bottom: 80px;">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Font</label></th>
                        <td>
                            <input type="button" name="ogp_font_url_slect" value="Upload" /><br>
                            <input name="ogp_font_url" type="text" value="<?php echo esc_html($ogp_font_url) ?>" style="width:60%; margin-top:4px;" readonly="readonly"/>
                            <p style="margin-top:10px;">Uploadable File Formats : ttf</p>
                            <?php
                            if(empty($ogp_font_url)){
                                echo '<p class="error-txt">Required Field</p>';
                            }
                                ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Font Size</label></th>
                        <td>
                            <input name="ogp_font_size" type="number" id="ogp_font_size" value="<?php echo esc_html($ogp_font_size) ?>" class="regular-text"/>
                            <?php
                            if(empty($ogp_font_size)){
                                echo '<p class="error-txt">Required Field</p>';
                            }
                            ?>
                        </td>

                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Font Color</label></th>
                        <td>
                            <input class="input_ogp_font_color" name="ogp_font_color" type="color" id="ogp_font_color" value="<?php  echo esc_html($ogp_font_color) ?>" class="regular-text"/>
                            <?php
                            if(empty($ogp_font_color)){
                                echo '<p class="error-txt">Required Field</p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Number Of Characters To Be New-lined</label></th>
                        <td>
                            <input name="ogp_new_line_char_length" type="number" id="ogp_new_line_char_length" value="<?php  echo esc_html($ogp_new_line_char_length) ?>" class="regular-text"/>
                            <?php
                            if(empty($ogp_new_line_char_length)){
                                echo '<p class="error-txt">Required Field</p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Background Image</label></th>
                        <td>
                            <input type="button" name="ogp_image_url_slect" value="Upload" /><br>
                            <input name="original_image" type="hidden" value="<?php echo esc_html($original_image) ?>" readonly="readonly"/>
                            <div id="ogp_image_url_thumbnail" class="uploded-thumbnail">
                            <?php foreach ($ogp_image_urls as $key => $url): ?>
                                                        <div class="box"><img src="<?php echo esc_url($url); ?>" alt="<?php echo esc_html($titles[$key]); ?>" style="height:128px; margin-top:4px;"/><p><?php echo esc_html($titles[$key]); ?></p></div>
                            <?php endforeach ?>
                            </div>
                            <?php
                            if(empty($original_image)){
                                echo '<p class="error-txt">Required Field</p>';
                            }
                                ?>
                            <input type="submit" name="preview" value="Preview" />
                            <div class="box"><img src="<?php echo $preview_image_path; ?>" alt="" style="height:128px; margin-top:4px;"/></div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Output Destination</label></th>
                        <td>
                            <strong><?php echo esc_url($file_path_to_img); ?></strong>
                            <br>
                            <br>
                            <span>※ When an article is saved, an image is automatically generated at the above image storage location.</span>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">Generated Image File Name</label></th>
                        <td>
                            <strong>ogp-[Post ID]</strong><br>
                            <p>e.g.　ogp-100.jpg</p>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></p>
        </form>
    </div>
</div>
<script type="text/javascript">
(function ($) {
    var image_uploader;
    var font_uploader;
    $("input:button[name=ogp_image_url_slect]").click(function(e) {
        e.preventDefault();
        if (image_uploader) {
            image_uploader.open();
            return;
        }
        image_uploader = wp.media({
            title: "Select an image",
            library: {
                type: "image",
                author: userSettings.uid
            },
            button: {
                text: "Select an image"
            },
            multiple: true
        });
        image_uploader.on("select", function() {

            $("input:hidden[name=original_image]").val("");
            $("#ogp_image_url_thumbnail").empty();

            var images = image_uploader.state().get("selection");
            images.each(function(file){
                var id = file.attributes.id;
                var title = file.attributes.title;
                var url = file.attributes.sizes.thumbnail.url;

                var idTmp = $("input:hidden[name=original_image]").val();
                if( idTmp != "" ) idTmp += ',';
                $("input:hidden[name=original_image]").val(idTmp+id);
                $("#ogp_image_url_thumbnail").append('<div class="box"><img src="'+url+'" alt="'+title+'"style="height:128px;"/><p>'+title+'</p><div>');
            });
        });
        image_uploader.open();
    });
    $("input:button[name=ogp_font_url_slect]").click(function(e) {
        e.preventDefault();
        if (font_uploader) {
            font_uploader.open();
            return;
        }
        font_uploader = wp.media({
            title: "Select a font",
            library: {
                search: "otf ttf",
                author: userSettings.uid
            },
            button: {
                text: "Select a font"
            },
            multiple: false
        });
        font_uploader.on("select", function() {
            $("input:text[name=ogp_font_url]").val("");

            var fonts = font_uploader.state().get("selection");
            fonts.each(function(file){
                var path = file.attributes.url;
                $("input:text[name=ogp_font_url]").val(path);
            });
        });
        font_uploader.open();
    });
})(jQuery);
</script>
<style type="text/css">
    .box {
        position: relative;
        display: inline-block;
    }
    .box p {
        position: absolute;
        top: 0;
        left: 0;
        padding: 10px;
        background-color: #000;
        color: #fff;
    }
    .input_ogp_font_color{
        width: 40px;
    }
    .error-txt {
        color: red;
    }
</style>

<?php
return;
