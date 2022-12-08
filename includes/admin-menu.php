<?php
// ---------------------------------------
// 設定画面
// ---------------------------------------
// function show_config()
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

wp_enqueue_media();
//$_POST['showtext_options'])があったら保存
if( isset($_POST['original_image']) && isset($_POST['ogp_font_url']) && isset($_POST['ogp_font_size']) && isset($_POST['original_image'])){
    check_admin_referer('ogp_config');
    update_option(self::PLUGIN_FONT_URL, $_POST['ogp_font_url']);
    update_option(self::PLUGIN_FONT_SIZE, $_POST['ogp_font_size']);
    update_option(self::PLUGIN_FONT_COLOR, $_POST['ogp_font_color']);
    update_option(self::PLUGIN_ORIGINAL_IMAGE, $_POST['original_image']);
    }
?>

<div class="wrap">
    <div id="icon-options-general"><br /></div><h2>OPG自動生成 設定</h2>
        <form action="" method="post">
<?php
wp_nonce_field('ogp_config');
$ogp_font_url =     get_option(self::PLUGIN_FONT_URL, null);
$ogp_font_size =     get_option(self::PLUGIN_FONT_SIZE, null);
$ogp_font_color =     get_option(self::PLUGIN_FONT_COLOR, '#000');
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
?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="inputtext">使用するフォント</label></th>
                    <td>
                        <input type="button" name="ogp_font_url_slect" value="選択" /><br>
                        <input name="ogp_font_url" type="text" value="<?php echo $ogp_font_url ?>" style="width:60%" readonly="readonly"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="inputtext">フォントサイズ</label></th>
                    <td><input name="ogp_font_size" type="text" id="ogp_font_size" value="<?php  echo $ogp_font_size ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="inputtext">フォントカラー</label></th>
                    <td><input name="ogp_font_color" type="color" id="ogp_font_color" value="<?php  echo $ogp_font_color ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="inputtext">テンプレート画像</label></th>
                    <td>
                        <input type="button" name="ogp_image_url_slect" value="選択" /><br>
                        <input name="original_image" type="hidden" value="<?php echo $original_image ?>" readonly="readonly"/>
                        <div id="ogp_image_url_thumbnail" class="uploded-thumbnail">
<?php foreach ($ogp_image_urls as $key => $url): ?>
                            <div class="box"><img src="<?php echo $url; ?>" alt="<?php echo $titles[$key]; ?>" style="height:128px;"/><p><?php echo $titles[$key]; ?></p></div>
<?php endforeach ?>
                        </div>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
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
            title: "画像を選択してください",
            /* ライブラリの一覧は画像のみにする */
            library: {
                type: "image",
                author: userSettings.uid
            },
            button: {
                text: "画像の選択"
            },
            multiple: true
        });
        image_uploader.on("select", function() {
            /* テキストフォームと表示されたサムネイル画像があればクリア */
            $("input:hidden[name=original_image]").val("");
            $("#ogp_image_url_thumbnail").empty();

            /* file の中に選択された画像の各種情報が入っている */
            var images = image_uploader.state().get("selection");
            images.each(function(file){
                //console.log(file.attributes);
                var id = file.attributes.id;
                var title = file.attributes.title;
                var url = file.attributes.sizes.thumbnail.url;

                /* テキストフォームに画像の URL を表示 */
                var idTmp = $("input:hidden[name=original_image]").val();
                if( idTmp != "" ) idTmp += ',';
                $("input:hidden[name=original_image]").val(idTmp+id);
                /* プレビュー用に選択されたサムネイル画像を表示 */
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
            title: "フォントを選択してください",
            /* ライブラリの一覧はフォントのみにする */
            library: {
                search: "otf ttf",
                author: userSettings.uid
            },
            button: {
                text: "フォントの選択"
            },
            /* 選択できる画像は 1 つだけにする */
            multiple: false
        });
        font_uploader.on("select", function() {
            $("input:text[name=ogp_font_url]").val("");

            var fonts = font_uploader.state().get("selection");
            /* file の中に選択された画像の各種情報が入っている */
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
        _display: inline;
    }
    .box p {
        position: absolute;
        top: 0;
        left: 0;
        padding: 10px;
        background-color: #000;
        color: #fff;
    }
</style>

<?php
return;
