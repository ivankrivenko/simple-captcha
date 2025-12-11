<?php
/**
 * Plugin Name: Simple Folder-based Captcha
 * Description: Генерация каптчи на основе изображений цифр и интеграция с Contact Form 7.
 * Version: 1.0.0
 * Author: ivankrivenko, OpenAI Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

define( 'SCAPTCHA_PLUGIN_FILE', __FILE__ );
define( 'SCAPTCHA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCAPTCHA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'SCAPTCHA_UPLOAD_SUBDIR', 'simple-captcha' );

define( 'SCAPTCHA_OPTION_GROUP', 'scaptcha_settings' );

define( 'SCAPTCHA_OPTION_NAME', 'scaptcha_options' );

require_once SCAPTCHA_PLUGIN_DIR . 'includes/class-simple-captcha.php';
require_once SCAPTCHA_PLUGIN_DIR . 'includes/class-simple-captcha-admin.php';
require_once SCAPTCHA_PLUGIN_DIR . 'includes/class-simple-captcha-cf7.php';
require_once SCAPTCHA_PLUGIN_DIR . 'includes/class-simple-captcha-login.php';

function scaptcha_init() {
$plugin = Simple_Captcha::get_instance();
$plugin->register();

    $admin = new Simple_Captcha_Admin( $plugin );
    $admin->register();

    $cf7 = new Simple_Captcha_CF7( $plugin );
    $cf7->register();

    $login = new Simple_Captcha_Login( $plugin );
    $login->register();
}
add_action( 'plugins_loaded', 'scaptcha_init' );

register_activation_hook( __FILE__, array( 'Simple_Captcha', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Simple_Captcha', 'deactivate' ) );

