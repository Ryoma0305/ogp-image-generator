<?php
// ---------------------------------------
// 設定画面
// ---------------------------------------
// function show_config()
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// require_once('../ogp-image-generator.php');

wp_enqueue_media();

if( isset($_POST['original_image']) && isset($_POST['ogp_font_url']) && isset($_POST['ogp_font_size'])){
    check_admin_referer('ogp_config');
    update_option(self::PLUGIN_FONT_URL, sanitize_url($_POST['ogp_font_url']));
    $options = array('options' => array('min_range' => 10, 'max_range' => 72));
    $font_size = filter_input( INPUT_POST, 'ogp_font_size', FILTER_VALIDATE_INT, $options);
    $font_size ? update_option(self::PLUGIN_FONT_SIZE, $font_size) : '';
    update_option(self::PLUGIN_FONT_COLOR, sanitize_hex_color($_POST['ogp_font_color']));
    update_option(self::PLUGIN_NEWLINE_CHAR_LENGTH, $_POST['ogp_new_line_char_length']);
    update_option(self::PLUGIN_ORIGINAL_IMAGE, $_POST['original_image']);
}

if(isset($_POST['preview'])){
    $base64_img = OgpImageGenerator::oig_generate_preview_image();
    var_dump($base64_img);
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
                            <div class="box"><img src="data:image/png;base64,<?php echo $base64_img; ?>" alt="" style="height:128px; margin-top:4px;"/></div>
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
