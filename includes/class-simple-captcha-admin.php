<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class Simple_Captcha_Admin {
/** @var Simple_Captcha */
private $plugin;

public function __construct( Simple_Captcha $plugin ) {
$this->plugin = $plugin;
}

public function register() {
add_action( 'admin_menu', array( $this, 'add_menu' ) );
add_action( 'admin_init', array( $this, 'register_settings' ) );
}

public function add_menu() {
add_options_page(
__( 'Simple Captcha', 'scaptcha' ),
__( 'Simple Captcha', 'scaptcha' ),
'manage_options',
'scaptcha-settings',
array( $this, 'render_settings_page' )
);
}

    public function register_settings() {
        register_setting( SCAPTCHA_OPTION_GROUP, SCAPTCHA_OPTION_NAME, array( $this, 'sanitize_options' ) );

        add_settings_section(
            'scaptcha_main',
            __( 'Основные настройки', 'scaptcha' ),
            '__return_false',
            'scaptcha-settings'
        );

        add_settings_field(
            'captcha_type',
            __( 'Тип каптчи', 'scaptcha' ),
            array( $this, 'render_captcha_type_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'code_length',
            __( 'Количество символов', 'scaptcha' ),
            array( $this, 'render_code_length_field' ),
            'scaptcha-settings',
'scaptcha_main'
);

add_settings_field(
'mode',
__( 'Источник изображений', 'scaptcha' ),
array( $this, 'render_mode_field' ),
'scaptcha-settings',
'scaptcha_main'
);

add_settings_field(
'digit_directory',
__( 'Папка с цифрами', 'scaptcha' ),
array( $this, 'render_digit_dir_field' ),
'scaptcha-settings',
'scaptcha_main'
);

        add_settings_field(
            'bg_directory',
            __( 'Папка с фонами', 'scaptcha' ),
            array( $this, 'render_bg_dir_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'enable_wp_login',
            __( 'Включить для wp-login.php', 'scaptcha' ),
            array( $this, 'render_enable_wp_login_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'enable_wc_registration',
            __( 'Включить для регистрации WooCommerce', 'scaptcha' ),
            array( $this, 'render_enable_wc_registration_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'enable_password_reset',
            __( 'Включить для сброса пароля', 'scaptcha' ),
            array( $this, 'render_enable_password_reset_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'yandex_server_key',
            __( 'Яндекс.Капча: секретный ключ', 'scaptcha' ),
            array( $this, 'render_yandex_server_key_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );

        add_settings_field(
            'yandex_client_key',
            __( 'Яндекс.Капча: ключ сайта', 'scaptcha' ),
            array( $this, 'render_yandex_client_key_field' ),
            'scaptcha-settings',
            'scaptcha_main'
        );
    }

    public function sanitize_options( $input ) {
        $output = $this->plugin->get_options();

        if ( isset( $input['captcha_type'] ) && in_array( $input['captcha_type'], array( 'custom', 'yandex' ), true ) ) {
            $output['captcha_type'] = $input['captcha_type'];
        }

        if ( isset( $input['code_length'] ) ) {
            $output['code_length'] = max( 1, absint( $input['code_length'] ) );
        }

        if ( isset( $input['mode'] ) && in_array( $input['mode'], array( 'dynamic', 'stored' ), true ) ) {
$output['mode'] = $input['mode'];
}

if ( isset( $input['digit_directory'] ) ) {
$output['digit_directory'] = rtrim( sanitize_text_field( $input['digit_directory'] ), '/\\' );
}

        if ( isset( $input['bg_directory'] ) ) {
            $output['bg_directory'] = rtrim( sanitize_text_field( $input['bg_directory'] ), '/\\' );
        }

        $output['enable_wp_login']        = ! empty( $input['enable_wp_login'] );
        $output['enable_wc_registration'] = ! empty( $input['enable_wc_registration'] );
        $output['enable_password_reset']  = ! empty( $input['enable_password_reset'] );

        if ( isset( $input['yandex_server_key'] ) ) {
            $output['yandex_server_key'] = sanitize_text_field( $input['yandex_server_key'] );
        }

        if ( isset( $input['yandex_client_key'] ) ) {
            $output['yandex_client_key'] = sanitize_text_field( $input['yandex_client_key'] );
        }

        return $output;
    }

public function render_settings_page() {
?>
<div class="wrap">
<h1><?php esc_html_e( 'Настройки Simple Captcha', 'scaptcha' ); ?></h1>
<form action="options.php" method="post">
<?php
settings_fields( SCAPTCHA_OPTION_GROUP );
do_settings_sections( 'scaptcha-settings' );
submit_button();
?>
</form>
</div>
<?php
}

public function render_code_length_field() {
$options = $this->plugin->get_options();
?>
<input type="number" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[code_length]' ); ?>" value="<?php echo esc_attr( $options['code_length'] ); ?>" min="1" max="10" />
<p class="description"><?php esc_html_e( 'Количество цифр в создаваемой каптче.', 'scaptcha' ); ?></p>
<?php
}

public function render_captcha_type_field() {
    $options = $this->plugin->get_options();
    ?>
    <select name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[captcha_type]' ); ?>">
        <option value="custom" <?php selected( $options['captcha_type'], 'custom' ); ?>><?php esc_html_e( 'Кастомная капча с цифрами', 'scaptcha' ); ?></option>
        <option value="yandex" <?php selected( $options['captcha_type'], 'yandex' ); ?>><?php esc_html_e( 'Яндекс.Капча', 'scaptcha' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'Выберите, какую капчу показывать на сайтах и формах.', 'scaptcha' ); ?></p>
    <?php
}

public function render_mode_field() {
$options = $this->plugin->get_options();
?>
<select name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[mode]' ); ?>">
<option value="dynamic" <?php selected( $options['mode'], 'dynamic' ); ?>><?php esc_html_e( 'Формировать на лету', 'scaptcha' ); ?></option>
<option value="stored" <?php selected( $options['mode'], 'stored' ); ?>><?php esc_html_e( 'Брать изображения из базы', 'scaptcha' ); ?></option>
</select>
<p class="description"><?php esc_html_e( 'В режиме "из базы" будет выбран ранее созданный вариант, если он существует.', 'scaptcha' ); ?></p>
<?php
}

public function render_digit_dir_field() {
$options = $this->plugin->get_options();
?>
<input type="text" class="regular-text" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[digit_directory]' ); ?>" value="<?php echo esc_attr( $options['digit_directory'] ); ?>" />
<p class="description"><?php esc_html_e( 'Путь к папке, содержащей подпапки с цифрами (0-9.png в каждой).', 'scaptcha' ); ?></p>
<?php
}

    public function render_bg_dir_field() {
        $options = $this->plugin->get_options();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[bg_directory]' ); ?>" value="<?php echo esc_attr( $options['bg_directory'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Путь к папке с PNG-фонами 200x50.', 'scaptcha' ); ?></p>
        <?php
    }

    public function render_enable_wp_login_field() {
        $options = $this->plugin->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[enable_wp_login]' ); ?>" value="1" <?php checked( $options['enable_wp_login'], true ); ?> />
            <?php esc_html_e( 'Добавить каптчу на форму входа wp-login.php.', 'scaptcha' ); ?>
        </label>
        <?php
    }

    public function render_enable_wc_registration_field() {
        $options = $this->plugin->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[enable_wc_registration]' ); ?>" value="1" <?php checked( $options['enable_wc_registration'], true ); ?> />
            <?php esc_html_e( 'Включить каптчу на форме регистрации WooCommerce.', 'scaptcha' ); ?>
        </label>
        <?php
    }

    public function render_enable_password_reset_field() {
        $options = $this->plugin->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[enable_password_reset]' ); ?>" value="1" <?php checked( $options['enable_password_reset'], true ); ?> />
            <?php esc_html_e( 'Добавить каптчу на форму восстановления пароля.', 'scaptcha' ); ?>
        </label>
        <?php
    }

    public function render_yandex_server_key_field() {
        $options = $this->plugin->get_options();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[yandex_server_key]' ); ?>" value="<?php echo esc_attr( $options['yandex_server_key'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Секретный ключ для серверной проверки токена Яндекс.Капчи.', 'scaptcha' ); ?></p>
        <?php
    }

    public function render_yandex_client_key_field() {
        $options = $this->plugin->get_options();
        ?>
        <input type="text" class="regular-text" name="<?php echo esc_attr( SCAPTCHA_OPTION_NAME . '[yandex_client_key]' ); ?>" value="<?php echo esc_attr( $options['yandex_client_key'] ); ?>" />
        <p class="description"><?php esc_html_e( 'Ключ сайта для рендеринга виджета Яндекс.Капчи на странице.', 'scaptcha' ); ?></p>
        <?php
    }
}

