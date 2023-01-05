<?php
/*
Plugin Name: SIG Small Menu
Plugin URI:  https://github.com/mark2me/sig-small-menu
Description: 利用佈景的自訂選單製作出側邊和手機版底部的固定位置選單。
Author:       Simon Chunag
Author URI:   https://github.com/mark2me
Version:      1.0
Text Domain:  sig-small-menu
Domain Path:  /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	return;
}


new SIG_SMALL_MENU();

class SIG_SMALL_MENU {

    const PLUGIN_SLUG = 'sig_smenu';

    public function __construct() {

        add_action( 'plugins_loaded', array( $this, 'load_update_checker' ) );

        add_action( 'after_setup_theme', function(){
            register_nav_menus( array(
                self::PLUGIN_SLUG => 'SIG自訂固定選單',
            ) );
        } );

        add_action('admin_menu', array( $this, 'add_menu_page') );

        // admin: register
        add_action( 'admin_init', array( $this, 'add_input_var' ) );

        // admin: style, scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_enqueue_scripts' ) );

        add_action( 'wp_footer' , array( $this, 'show_small_menu' ), 999 );

        // nav menu iocn
        add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'menu_custom_fields_icon' ), 10, 5 );
        add_action( 'wp_update_nav_menu_item', array( $this, 'save_menu_custom_fields_icon' ), 10, 2 );
        add_filter( 'nav_menu_item_title', array( $this, 'show_menu_custom_fields_icon' ), 10, 3 );

        // icon style
        add_action( 'wp_enqueue_scripts', array( $this, 'get_icon_style') );

    }

    public function add_menu_page(){
        add_menu_page(
            '固定選單設定',
            '自訂側邊、底部選單',
            'manage_options',
            self::PLUGIN_SLUG,
            array( $this, 'plugin_settings_page')
        );
    }

    public function add_input_var() {

        register_setting( 'sig-small-menu', '_sig_small_pc', array('type'=>'array') );
        register_setting( 'sig-small-menu', '_sig_small_mb', array('type'=>'array') );
    }

    public function add_admin_enqueue_scripts( $hook_suffix ) {

        // page setting
        if( in_array( $hook_suffix, array( 'toplevel_page_'.self::PLUGIN_SLUG )) ) {
            wp_enqueue_style( 'sig-small-menu', plugin_dir_url( __FILE__ ) . 'assets/css/setting.css', array() );
            wp_enqueue_style( 'fontello', plugin_dir_url( __FILE__ ) . 'assets/icon/css/fontello.css' );

            // use wp color picker
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'my-color-picker', plugins_url('assets/js/colorPicker/color-picker.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
        }

        // select2
        if( 'nav-menus.php' === $hook_suffix ) {
            wp_enqueue_style( 'select2', plugin_dir_url( __FILE__ ) . 'assets/js/select2/select2.min.css', array(), '4.1.0');
            wp_enqueue_script( 'select2', plugin_dir_url( __FILE__ ) . 'assets/js/select2/select2.min.js', array(), '4.1.0', true );
            wp_enqueue_script( 'select2_conf', plugin_dir_url( __FILE__ ) . 'assets/js/select2/config.js', array(), '4.1.0', true );
            wp_enqueue_style( 'fontello', plugin_dir_url( __FILE__ ) . 'assets/icon/css/fontello.css' );
        }
    }

    /**
     *  Get option
     */
    private function plugin_options(){

        $pc = get_option( '_sig_small_pc' );
        $mb = get_option( '_sig_small_mb' );

        return [
            'pc' => [
                'pos'       => ( empty($pc['pos']) or ( !empty($pc['pos']) && ($pc['pos'] !== 'left') ) ) ? 'right' : 'left',
                'width'     => ( !empty($pc['width']) ? $pc['width']: 50 ),
                'height'    => ( !empty($pc['height']) ? $pc['height']: 50 ),
                'br_size'   => ( !empty($pc['br_size']) ? $pc['br_size']: 0 ),
                'br_color'  => ( !empty($pc['br_color']) ? $pc['br_color']: '#ffffff' ),
                'size'      => ( !empty($pc['size']) ? $pc['size']: 0 ),
                'color'     => ( !empty($pc['color']) ? $pc['color']: '#ffffff' ),
                'icon_size' => ( !empty($pc['icon_size']) ? $pc['icon_size']: 0 ),
                'icon_color'=> ( !empty($pc['icon_color']) ? $pc['icon_color']: '#ffffff' ),
                'bgcolor'   => ( !empty($pc['bgcolor']) ? $pc['bgcolor']: '#333333' ),
                'margin'    => ( !empty($pc['margin']) ? $pc['margin']: 0 ),
                'close'     => ( (!empty($pc['close']) && $pc['close'] === 'yes' ) ? 'yes': 'no' ),
            ],
            'mb' => [
                'pos'       => ( empty($mb['pos']) or ( !empty($mb['pos']) && ($mb['pos'] !== 'top') ) ) ? 'bottom' : 'top',
                'height'    => ( !empty($mb['height']) ? $mb['height']: 60 ),
                'br_size'   => ( !empty($mb['br_size']) ? $mb['br_size']: 0 ),
                'br_color'  => ( !empty($mb['br_color']) ? $mb['br_color']: '#ffffff' ),
                'size'      => ( !empty($mb['size']) ? $mb['size']: 0 ),
                'color'     => ( !empty($mb['color']) ? $mb['color']: '#ffffff' ),
                'icon_size' => ( !empty($mb['icon_size']) ? $mb['icon_size']: 0 ),
                'icon_color'=> ( !empty($mb['icon_color']) ? $mb['icon_color']: '#ffffff' ),
                'bgcolor'   => ( !empty($mb['bgcolor']) ? $mb['bgcolor']: '#333333' ),
                'break'     => ( !empty($mb['break']) ? $mb['break']: 575 ),


            ],
        ];

    }

    public function plugin_settings_page() {

        $opt = $this->plugin_options();


?>
<div class="wrap">

    <h2>固定選單參數設定</h2>

    <form method="post" action="options.php">

        <?php settings_fields('sig-small-menu'); ?>

        <div class="postbox">

            <h2 class="sig-panel-title open">桌機版設定</h2>
            <div class="sig-panel-settings" style="display: block;">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">顯示位置</th>
                        <td>
                            <label>頁面左側：<input type="radio" name="_sig_small_pc[pos]" value="left" <?php echo checked($opt['pc']['pos'],'left',false)?>/></label>，
                            <label>頁面右側：<input type="radio" name="_sig_small_pc[pos]" value="right" <?php echo checked($opt['pc']['pos'],'right',false)?>/></label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目寬高</th>
                        <td>
                            寬：<input type="number" class="small-text" name="_sig_small_pc[width]" min="10" value="<?php echo esc_attr( $opt['pc']['width'] );?>" /> px
                            ，高：<input type="number" class="small-text" name="_sig_small_pc[height]" min="10" value="<?php echo esc_attr( $opt['pc']['height'] );?>" /> px
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目框線</th>
                        <td>
                            <div class="flex-middle">
                                <p>
                                    <label>大小：</label>
                                    <input type="number" class="small-text" name="_sig_small_pc[br_size]" min="0" value="<?php echo esc_attr( $opt['pc']['br_size'] );?>" /> px (設為 0 表示不顯示)
                                </p>
                                <p>
                                    <label>顏色：</label>
                                    <input type="text" class="sig-color-field" name="_sig_small_pc[br_color]" value="<?php echo esc_attr( $opt['pc']['br_color'] );?>" />
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目文字</th>
                        <td>
                            <div class="flex-middle">
                                <p>
                                    <label>大小：</label>
                                    <input type="number" class="small-text" name="_sig_small_pc[size]" min="0" value="<?php echo esc_attr( $opt['pc']['size'] );?>" /> px (設為 0 表示不顯示)
                                </p>
                                <p>
                                    <label>顏色：</label>
                                    <input type="text" class="sig-color-field" name="_sig_small_pc[color]" value="<?php echo esc_attr( $opt['pc']['color'] );?>" />
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目Icon</th>
                        <td>
                            <div class="flex-middle">
                                <p>
                                    <label>大小：</label>
                                    <input type="number" class="small-text" name="_sig_small_pc[icon_size]" min="0" value="<?php echo esc_attr( $opt['pc']['icon_size'] );?>" /> px (設為 0 表示不顯示)
                                </p>
                                <p>
                                    <label>顏色：</label>
                                    <input type="text" class="sig-color-field" name="_sig_small_pc[icon_color]" value="<?php echo esc_attr( $opt['pc']['icon_color'] );?>" />
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目背景色</th>
                        <td>
                            <input type="text" class="sig-color-field" name="_sig_small_pc[bgcolor]" value="<?php echo esc_attr( $opt['pc']['bgcolor'] );?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目上下間隔</th>
                        <td>
                            <input type="number" class="small-text" name="_sig_small_pc[margin]" min="0" value="<?php echo esc_attr( $opt['pc']['margin'] );?>" /> px
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">項目收合</th>
                        <td>
                            <label><input type="checkbox" name="_sig_small_pc[close]" value="yes" <?php echo checked( $opt['pc']['close'], 'yes' );?> /> 選單可點擊收合</label>
                        </td>
                    </tr>
                </table>
            </div>

        </div>

        <div class="postbox">

            <h2 class="sig-panel-title">手機版設定</h2>
            <div class="sig-panel-settings" style="display: none;">
                <table class="form-table">
                <tr valign="top">
                    <th scope="row">顯示位置</th>
                    <td>
                        <label>頁面上方：<input type="radio" name="_sig_small_mb[pos]" value="top" <?php echo checked($opt['mb']['pos'],'top',false)?>/></label>，
                        <label>頁面下方：<input type="radio" name="_sig_small_mb[pos]" value="right" <?php echo checked($opt['mb']['pos'],'bottom',false)?>/></label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">項目高</th>
                    <td>
                        高：<input type="number" class="small-text" name="_sig_small_mb[height]" min="20" value="<?php echo esc_attr( $opt['mb']['height'] );?>" />px
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">項目框線</th>
                    <td>
                        <div class="flex-middle">
                            <p>
                                <label>大小：</label>
                                <input type="number" class="small-text" name="_sig_small_mb[br_size]" min="0" value="<?php echo esc_attr( $opt['mb']['br_size'] );?>" /> px (設為 0 表示不顯示)
                            </p>
                            <p>
                                <label>顏色：</label>
                                <input type="text" class="sig-color-field" name="_sig_small_mb[br_color]" value="<?php echo esc_attr( $opt['mb']['br_color'] );?>" />
                            </p>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">項目文字</th>
                    <td>
                        <div class="flex-middle">
                            <p>
                                <label>大小：</label>
                                <input type="number" class="small-text" name="_sig_small_mb[size]" min="0" value="<?php echo esc_attr( $opt['mb']['size'] );?>" /> px (設為 0 表示不顯示)
                            </p>
                            <p>
                                <label>顏色：</label>
                                <input type="text" class="sig-color-field" name="_sig_small_mb[color]" value="<?php echo esc_attr( $opt['mb']['color'] );?>" />
                            </p>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">項目Icon</th>
                    <td>
                        <div class="flex-middle">
                            <p>
                                <label>大小：</label>
                                <input type="number" class="small-text" name="_sig_small_mb[icon_size]" min="0" value="<?php echo esc_attr( $opt['mb']['icon_size'] );?>" /> px (設為 0 表示不顯示)
                            </p>
                            <p>
                                <label>顏色：</label>
                                <input type="text" class="sig-color-field" name="_sig_small_mb[icon_color]" value="<?php echo esc_attr( $opt['mb']['icon_color'] );?>" />
                            </p>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">項目背景色</th>
                    <td>
                        <input type="text" class="sig-color-field" name="_sig_small_mb[bgcolor]" value="<?php echo esc_attr( $opt['mb']['bgcolor'] );?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">界定最大寬度</th>
                    <td>
                        <input type="number" class="small-text" name="_sig_small_mb[break]" min="0" value="<?php echo esc_attr( $opt['mb']['break'] );?>" /> px (螢幕超過此寬度即為桌機版)
                    </td>
                </tr>
                </table>
            </div>

        </div>

        <?php submit_button(); ?>

    </form>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.sig-panel-title').on('click',function(){
        $(this).toggleClass('open');
        $(this).siblings('.sig-panel-settings').slideToggle();
    });
});
 </script>
<?php

    }


    /**
     *  前台顯示
     */
    public function show_small_menu() {

        $theme_location = self::PLUGIN_SLUG;
        $opt = $this->plugin_options();

        $args = [
            'theme_location' => $theme_location,
            'container_class' => 'sig-small-menu-wrap',
            'echo' => false,
            'fallback_cb' => false,
        ];

        if( isset($opt['pc']['close']) && $opt['pc']['close'] === 'yes' ){
            $args['items_wrap'] = '<a href="#" class="sig-small-close close-'.esc_attr($opt['pc']['pos']).'"><i class="sicon sicon-right-open"></i><span>CLOSE</span></a><ul id="%1$s" class="%2$s">%3$s</ul>';
        }

        $menu = wp_nav_menu($args);

        if( empty($menu) ) return;


?>
<script type="text/javascript">jQuery(document).ready(function($) {
$('a[href=#top]').on('click', function(){
    $("html, body").animate({scrollTop: 0}, 1000);
    return false;
});
$('a.sig-small-close').on('click', function(e){
    e.preventDefault();
    $('.sig-small-menu-wrap').toggleClass('nav-close');
    if( $('.sig-small-menu-wrap').hasClass('nav-close') ){
        $('.sig-small-menu-wrap').animate({ "<?php echo esc_attr($opt['pc']['pos']) ?>": "-<?php echo esc_attr($opt['pc']['width'])?>px"}, 800, function(){ $('.sig-small-close').find('.sicon-right-open').removeClass('sicon-right-open').addClass('sicon-left-open'); $('.sig-small-close span').text('OPEN'); } );
    }else{
        $('.sig-small-menu-wrap').animate({ "<?php echo esc_attr($opt['pc']['pos']) ?>":0}, 800, function(){ $('.sig-small-close').find('.sicon-left-open').removeClass('sicon-left-open').addClass('sicon-right-open'); $('.sig-small-close span').text('CLOSE'); } );
    }
});
});</script>
<style type="text/css">
.sig-small-menu-wrap{position:fixed;z-index:9999;}
.sig-small-menu-wrap ul{list-style:none;margin:0;padding:0;}
.sig-small-menu-wrap li a i{position: relative;}
.sig-small-menu-wrap .scartnums{position: absolute;font-style:normal;text-align:center;font-size:10px;color: #fff;background-color:rgba(0,0,0,0.6);top:-5px;right:-5px;display:inline-block;width:15px;height:15px;line-height:15px;border-radius:50%;}
.sig-small-menu-wrap .sig-small-close{position: absolute;display:inline-block;width:30px;height:auto;padding: 10px 0;text-align:center;line-height: 15px; top:<?php echo esc_attr($opt['pc']['margin'])?>px;<?php echo esc_attr($opt['pc']['pos']) ?>:<?php echo (esc_attr($opt['pc']['width']) - esc_attr($opt['pc']['br_size'])) ?>px;background-color:<?php echo esc_attr($opt['pc']['bgcolor']) ?>;color:<?php echo esc_attr($opt['pc']['color']) ?>;border:<?php echo esc_attr($opt['pc']['br_size']) ?>px solid <?php echo esc_attr($opt['pc']['br_color']);?> }
.sig-small-menu-wrap .sig-small-close i:before{margin-bottom: 8px;font-size: 16px;}
.sig-small-menu-wrap .sig-small-close span{writing-mode: vertical-rl;}
@media (min-width: <?php echo esc_attr($opt['mb']['break'])+1 ?>px){
.sig-small-menu-wrap{ top: 50%;bottom: auto; transform: translateY(-50%); <?php echo esc_attr($opt['pc']['pos']) ?>: 0; }
.sig-small-menu-wrap li{ border: <?php echo esc_attr($opt['pc']['br_size']) ?>px solid <?php echo esc_attr($opt['pc']['br_color']) ?>; background-color: <?php echo esc_attr($opt['pc']['bgcolor']) ?>; height: <?php echo esc_attr($opt['pc']['height']) ?>px; width: <?php echo esc_attr($opt['pc']['width']) ?>px; margin: <?php echo esc_attr($opt['pc']['margin']) ?>px 0;}
.sig-small-menu-wrap li a{ display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; }
.sig-small-menu-wrap li a .sname{font-size: <?php echo esc_attr($opt['pc']['size']) ?>px; color: <?php echo esc_attr($opt['pc']['color']) ?>;<?php if( empty(esc_attr($opt['pc']['size'])) ) echo 'display: none;' ?>}
.sig-small-menu-wrap li a i{font-size: <?php echo esc_attr($opt['pc']['icon_size']) ?>px; color: <?php echo esc_attr($opt['pc']['icon_color']) ?>;<?php if( empty(esc_attr($opt['pc']['icon_size'])) ) echo 'display: none;' ?>}
<?php if( esc_attr($opt['pc']['margin']) === '0' ) echo '.sig-small-menu-wrap li+li{border-top-width:0}';?>
}
@media (max-width: <?php echo esc_attr($opt['mb']['break']) ?>px){
body{padding-<?php echo esc_attr($opt['mb']['pos'])?>: <?php echo esc_attr($opt['mb']['height']) ?>px !important;}
#sig_smenu_space{ height: <?php echo esc_attr($opt['mb']['height']) ?>px; }
.sig-small-menu-wrap{ width: 100%; <?php echo esc_attr($opt['mb']['pos']) ?>: 0; }
.sig-small-menu-wrap ul{ display: flex; }
.sig-small-menu-wrap li{ border-top:<?php echo esc_attr($opt['mb']['br_size']) ?>px solid <?php echo esc_attr($opt['mb']['br_color']) ?>; background-color: <?php echo esc_attr($opt['mb']['bgcolor']) ?>; flex: 1 0 0%; }
.sig-small-menu-wrap li+li{ border-left:<?php echo esc_attr($opt['mb']['br_size']) ?>px solid <?php echo esc_attr($opt['mb']['br_color']) ?>; }
.sig-small-menu-wrap li a{ display: flex; align-items: center; flex-direction: column; justify-content: center; padding-top:5px;height: <?php echo esc_attr($opt['mb']['height']) ?>px; }
.sig-small-menu-wrap li a .sname{font-size: <?php echo esc_attr($opt['mb']['size']) ?>px;color: <?php echo esc_attr($opt['mb']['color']) ?>;<?php if( empty(esc_attr($opt['mb']['size'])) ) echo 'display: none;' ?>}
.sig-small-menu-wrap li a i{font-size: <?php echo esc_attr($opt['mb']['icon_size']) ?>px;color: <?php echo esc_attr($opt['mb']['icon_color']) ?>;<?php if( empty(esc_attr($opt['mb']['icon_size'])) ) echo 'display: none;' ?>}
.sig-small-menu-wrap .sig-small-close{display: none;}
}

</style>
    <?php
        echo $menu;

    }


    public function menu_custom_fields_icon( $item_id, $post, $depth, $args, $current_object_id ) {

        // only add for some menu
        $theme_location = self::PLUGIN_SLUG;
        $locations = get_nav_menu_locations();

        if( isset($locations[$theme_location]) ){
            $menu = wp_get_nav_menu_object( $locations[$theme_location] );
            if( !empty($menu) && $menu->term_id !== absint( get_user_option( 'nav_menu_recently_edited' ) )) return;
        }

        // all icons
        $json = file_get_contents( plugin_dir_path( __FILE__ ) . 'assets/icon/config.json' );
        $icons = json_decode($json ,true);

        //
        $icon_name = get_post_meta( $item_id, 'sig_menu_item_icon', true );

        ?>
        <div style="clear: both;">
            <span class="description">選擇圖示</span><br />

            <div class="logged-input-holder">
                <select class="menu_item_icon select2" name="menu_item_icon[<?php echo $item_id ;?>]" id="menu-item-icon-<?php echo $item_id ;?>" style="width:100%;">
                    <option value="">--</option>
                    <?php foreach($icons['glyphs'] as $i): ?>
                    <option value="<?php echo $icons['css_prefix_text'].$i['css']?>" <?php selected( $icon_name, $icons['css_prefix_text'].$i['css'] ); ?>><?php echo $i['css']?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }


    public function save_menu_custom_fields_icon( $menu_id, $menu_item_db_id ) {

        $data = '';

        if ( isset( $_POST['menu_item_icon'][$menu_item_db_id]  ) ) {
            $data = sanitize_text_field( $_POST['menu_item_icon'][$menu_item_db_id] );
        }

        if( !empty($data) ){
            update_post_meta( $menu_item_db_id, 'sig_menu_item_icon', $data );
        }else{
            delete_post_meta( $menu_item_db_id, 'sig_menu_item_icon' );
        }
    }


    public function show_menu_custom_fields_icon( $title, $item, $args ) {

        if( is_object( $item ) && isset( $item->ID ) && $args->theme_location === self::PLUGIN_SLUG ) {

            $menu_item_icon = get_post_meta( $item->ID, 'sig_menu_item_icon', true );
            $cart_nums = '';

            if( wc_get_cart_url() === $item->url ){
                $cart_nums = WC()->cart->get_cart_contents_count();
                $cart_nums = ( $cart_nums > 0 ) ? '<span class="scartnums">'.$cart_nums.'</span>' : '';
            }

            $icon = ( ! empty( $menu_item_icon ) ) ? '<i class="sicon '.$menu_item_icon.'">' . $cart_nums . '</i>' : '';

            return sprintf('%s<div class="sname">%s</div>', $icon, $title);
        }

        return $title;
    }


    public function get_icon_style(){
        wp_enqueue_style( 'fontello', plugin_dir_url(__FILE__). 'assets/icon/css/fontello.css' );
    }

    public function load_update_checker() {

        require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
        $myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/mark2me/sig-small-menu/',
            __FILE__,
            'sig-small-menu'
        );
    }

}

