<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Simple_Captcha_Login {
    /** @var Simple_Captcha */
    private $plugin;

    public function __construct( Simple_Captcha $plugin ) {
        $this->plugin = $plugin;
    }

    public function register() {
        add_action( 'login_form', array( $this, 'render_login_captcha' ) );
        add_filter( 'authenticate', array( $this, 'validate_login_captcha' ), 30, 3 );
        add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_assets' ) );

        add_action( 'lostpassword_form', array( $this, 'render_lostpassword_captcha' ) );
        add_action( 'lostpassword_post', array( $this, 'validate_lostpassword_captcha' ) );

        add_action( 'woocommerce_register_form', array( $this, 'render_woocommerce_register_captcha' ) );
        add_filter( 'woocommerce_registration_errors', array( $this, 'validate_woocommerce_registration' ), 10, 3 );
    }

    public function render_login_captcha() {
        if ( ! $this->should_show_wp_login_captcha() ) {
            return;
        }

        echo $this->plugin->render_captcha_markup( $this->plugin->get_captcha() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function validate_login_captcha( $user, $username, $password ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        if ( ! $this->should_show_wp_login_captcha() ) {
            return $user;
        }

        $captcha_type = $this->plugin->get_option( 'captcha_type', 'custom' );

        if ( 'yandex' === $captcha_type ) {
            $token = isset( $_POST['smart-token'] ) ? wp_unslash( $_POST['smart-token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( empty( $token ) ) {
                return new WP_Error( 'scaptcha_missing', __( 'Подтвердите, что вы не робот.', 'scaptcha' ) );
            }

            if ( ! $this->plugin->validate( $token, '' ) ) {
                return new WP_Error( 'scaptcha_invalid', __( 'Проверка капчи не пройдена.', 'scaptcha' ) );
            }

            return $user;
        }

        $token = isset( $_POST['scaptcha_token'] ) ? wp_unslash( $_POST['scaptcha_token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $input = isset( $_POST['scaptcha_input'] ) ? wp_unslash( $_POST['scaptcha_input'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( empty( $token ) || empty( $input ) ) {
            return new WP_Error( 'scaptcha_missing', __( 'Введите цифры с изображения.', 'scaptcha' ) );
        }

        if ( ! $this->plugin->validate( $token, $input ) ) {
            return new WP_Error( 'scaptcha_invalid', __( 'Код не совпадает с изображением.', 'scaptcha' ) );
        }

        return $user;
    }

    public function enqueue_login_assets() {
        if ( ! $this->should_show_wp_login_captcha() && ! $this->should_show_password_reset_captcha() ) {
            return;
        }

        $captcha_type = $this->plugin->get_option( 'captcha_type', 'custom' );

        wp_enqueue_style(
            'scaptcha-front',
            SCAPTCHA_PLUGIN_URL . 'assets/css/simple-captcha.css',
            array(),
            '1.0.0'
        );

        if ( 'yandex' === $captcha_type ) {
            wp_enqueue_script(
                'yandex-smart-captcha',
                'https://smartcaptcha.yandexcloud.net/captcha.js?render=onload',
                array(),
                null,
                true
            );
        }

        wp_enqueue_script(
            'scaptcha-front',
            SCAPTCHA_PLUGIN_URL . 'assets/js/simple-captcha.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_localize_script(
            'scaptcha-front',
            'SCaptcha',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'scaptcha_refresh' ),
                'captchaType'  => $captcha_type,
                'yandexSiteKey'=> $this->plugin->get_option( 'yandex_client_key', '' ),
            )
        );
    }

    public function render_lostpassword_captcha() {
        if ( ! $this->should_show_password_reset_captcha() ) {
            return;
        }

        echo $this->plugin->render_captcha_markup( $this->plugin->get_captcha() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function validate_lostpassword_captcha( $errors ) {
        if ( ! $this->should_show_password_reset_captcha() ) {
            return;
        }

        $captcha_type = $this->plugin->get_option( 'captcha_type', 'custom' );

        if ( 'yandex' === $captcha_type ) {
            $token = isset( $_POST['smart-token'] ) ? wp_unslash( $_POST['smart-token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( empty( $token ) ) {
                $errors->add( 'scaptcha_missing', __( 'Подтвердите, что вы не робот.', 'scaptcha' ) );

                return;
            }

            if ( ! $this->plugin->validate( $token, '' ) ) {
                $errors->add( 'scaptcha_invalid', __( 'Проверка капчи не пройдена.', 'scaptcha' ) );
            }

            return;
        }

        $token = isset( $_POST['scaptcha_token'] ) ? wp_unslash( $_POST['scaptcha_token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $input = isset( $_POST['scaptcha_input'] ) ? wp_unslash( $_POST['scaptcha_input'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( empty( $token ) || empty( $input ) ) {
            $errors->add( 'scaptcha_missing', __( 'Введите цифры с изображения.', 'scaptcha' ) );

            return;
        }

        if ( ! $this->plugin->validate( $token, $input ) ) {
            $errors->add( 'scaptcha_invalid', __( 'Код не совпадает с изображением.', 'scaptcha' ) );
        }
    }

    public function render_woocommerce_register_captcha() {
        if ( ! $this->should_show_woocommerce_captcha() ) {
            return;
        }

        echo '<p class="form-row form-row-wide">';
        echo $this->plugin->render_captcha_markup( $this->plugin->get_captcha() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</p>';
    }

    public function validate_woocommerce_registration( $errors, $username, $email ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
        if ( ! $this->should_show_woocommerce_captcha() ) {
            return $errors;
        }

        $captcha_type = $this->plugin->get_option( 'captcha_type', 'custom' );

        if ( 'yandex' === $captcha_type ) {
            $token = isset( $_POST['smart-token'] ) ? wp_unslash( $_POST['smart-token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if ( empty( $token ) ) {
                $errors->add( 'scaptcha_missing', __( 'Подтвердите, что вы не робот.', 'scaptcha' ) );

                return $errors;
            }

            if ( ! $this->plugin->validate( $token, '' ) ) {
                $errors->add( 'scaptcha_invalid', __( 'Проверка капчи не пройдена.', 'scaptcha' ) );
            }

            return $errors;
        }

        $token = isset( $_POST['scaptcha_token'] ) ? wp_unslash( $_POST['scaptcha_token'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $input = isset( $_POST['scaptcha_input'] ) ? wp_unslash( $_POST['scaptcha_input'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if ( empty( $token ) || empty( $input ) ) {
            $errors->add( 'scaptcha_missing', __( 'Введите цифры с изображения.', 'scaptcha' ) );

            return $errors;
        }

        if ( ! $this->plugin->validate( $token, $input ) ) {
            $errors->add( 'scaptcha_invalid', __( 'Код не совпадает с изображением.', 'scaptcha' ) );
        }

        return $errors;
    }

    private function should_show_wp_login_captcha() {
        return (bool) $this->plugin->get_option( 'enable_wp_login' );
    }

    private function should_show_password_reset_captcha() {
        return (bool) $this->plugin->get_option( 'enable_password_reset' );
    }

    private function should_show_woocommerce_captcha() {
        return (bool) $this->plugin->get_option( 'enable_wc_registration' );
    }
}

