<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Simple_Captcha_CF7 {
    /** @var Simple_Captcha */
    private $plugin;

    public function __construct( Simple_Captcha $plugin ) {
        $this->plugin = $plugin;
    }

    public function register() {
        add_action( 'wpcf7_init', array( $this, 'register_tag' ) );
        add_filter( 'wpcf7_validate_simplecaptcha', array( $this, 'validate' ), 10, 2 );
        add_filter( 'wpcf7_validate_simplecaptcha*', array( $this, 'validate' ), 10, 2 );
        add_filter( 'wpcf7_validate_simple_captcha', array( $this, 'validate' ), 10, 2 );
        add_filter( 'wpcf7_validate_simple_captcha*', array( $this, 'validate' ), 10, 2 );
    }

    public function register_tag() {
        wpcf7_add_form_tag(
            array( 'simplecaptcha', 'simplecaptcha*', 'simple_captcha', 'simple_captcha*' ),
            array( $this, 'render' ),
            array( 'name-attr' => true )
        );
    }

    public function render( $tag ) {
        $captcha = $this->plugin->get_captcha();

        $html  = '<span class="wpcf7-form-control-wrap scaptcha">';
        $html .= $this->plugin->render_captcha_markup( $captcha );
        $html .= '</span>';

        return $html;
    }

    public function validate( $result, $tag ) {
        $token = isset( $_POST['scaptcha_token'] ) ? wp_unslash( $_POST['scaptcha_token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $input = isset( $_POST['scaptcha_input'] ) ? wp_unslash( $_POST['scaptcha_input'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( empty( $token ) || empty( $input ) ) {
            $result->invalidate( $tag, __( 'Введите цифры с изображения.', 'scaptcha' ) );

            return $result;
        }

        $is_valid = $this->plugin->validate( $token, $input );
        if ( ! $is_valid ) {
            $result->invalidate( $tag, __( 'Код не совпадает с изображением.', 'scaptcha' ) );
        }

        return $result;
    }
}

