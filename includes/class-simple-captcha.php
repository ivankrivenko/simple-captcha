<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Simple_Captcha {
    /**
     * Singleton instance.
     *
     * @var Simple_Captcha
     */
    private static $instance;

    /**
     * Plugin options.
     *
     * @var array
     */
    private $options;

    /**
     * Table name for captcha records.
     *
     * @var string
     */
    private $table_name;

    private function __construct() {
        global $wpdb;

        $this->table_name = $wpdb->prefix . 'scaptcha_images';
        $this->options    = $this->get_options();
    }

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register() {
        add_shortcode( 'simple_captcha', array( $this, 'shortcode' ) );
        add_action( 'wp_ajax_scaptcha_refresh', array( $this, 'ajax_refresh' ) );
        add_action( 'wp_ajax_nopriv_scaptcha_refresh', array( $this, 'ajax_refresh' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public static function activate() {
        self::create_table();
        self::ensure_directories();
    }

    public static function deactivate() {
        // No cleanup by default.
    }

    private static function create_table() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'scaptcha_images';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            image_name VARCHAR(255) NOT NULL,
            code VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private static function ensure_directories() {
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit( $upload_dir['basedir'] ) . SCAPTCHA_UPLOAD_SUBDIR;

        wp_mkdir_p( $base_dir . '/digits' );
        wp_mkdir_p( $base_dir . '/bg' );
        wp_mkdir_p( $base_dir . '/generated' );
    }

    public function get_options() {
        $defaults = array(
            'code_length'     => 6,
            'mode'            => 'dynamic',
            'digit_directory' => wp_upload_dir()['basedir'] . '/' . SCAPTCHA_UPLOAD_SUBDIR . '/digits',
            'bg_directory'    => wp_upload_dir()['basedir'] . '/' . SCAPTCHA_UPLOAD_SUBDIR . '/bg',
        );

        return wp_parse_args( get_option( SCAPTCHA_OPTION_NAME, array() ), $defaults );
    }

    public function get_option( $key, $default = null ) {
        return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
    }

    public function refresh_options() {
        $this->options = $this->get_options();
    }

    public function enqueue_assets() {
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
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'scaptcha_refresh' ),
            )
        );
    }

    public function shortcode() {
        $captcha = $this->get_captcha();
        return $this->render_captcha_markup( $captcha );
    }

    public function ajax_refresh() {
        check_ajax_referer( 'scaptcha_refresh', 'nonce' );

        $captcha = $this->get_captcha( true );

        wp_send_json_success( $captcha );
    }

    public function render_captcha_markup( $captcha ) {
        if ( empty( $captcha['image_url'] ) || empty( $captcha['token'] ) ) {
            return '<p>' . esc_html__( 'Не удалось загрузить каптчу.', 'scaptcha' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="scaptcha-wrapper" data-token="<?php echo esc_attr( $captcha['token'] ); ?>">
            <div class="scaptcha-image">
                <img src="<?php echo esc_url( $captcha['image_url'] ); ?>" alt="captcha" width="200" height="50" loading="lazy" />
            </div>
            <button type="button" class="scaptcha-refresh" aria-label="<?php esc_attr_e( 'Обновить каптчу', 'scaptcha' ); ?>">&#8635;</button>
            <input type="hidden" name="scaptcha_token" value="<?php echo esc_attr( $captcha['token'] ); ?>" />
            <input type="text" name="scaptcha_input" placeholder="<?php esc_attr_e( 'Введите цифры', 'scaptcha' ); ?>" required />
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_captcha( $force_generate = false ) {
        $this->refresh_options();
        $mode = $this->get_option( 'mode', 'dynamic' );

        if ( ! $force_generate && 'stored' === $mode ) {
            $existing = $this->get_random_stored();
            if ( $existing ) {
                return $existing;
            }
        }

        return $this->generate_captcha();
    }

    private function get_random_stored() {
        global $wpdb;

        $row = $wpdb->get_row( "SELECT id, image_name FROM {$this->table_name} ORDER BY RAND() LIMIT 1", ARRAY_A );

        if ( empty( $row ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        $file_path  = trailingslashit( $upload_dir['basedir'] ) . SCAPTCHA_UPLOAD_SUBDIR . '/generated/' . $row['image_name'];

        if ( ! file_exists( $file_path ) ) {
            return null;
        }

        return array(
            'image_url' => trailingslashit( $upload_dir['baseurl'] ) . SCAPTCHA_UPLOAD_SUBDIR . '/generated/' . $row['image_name'],
            'token'     => (int) $row['id'],
        );
    }

    private function generate_captcha() {
        $code_length   = absint( $this->get_option( 'code_length', 6 ) );
        $code_length   = $code_length > 0 ? $code_length : 6;
        $digit_dir     = $this->get_option( 'digit_directory' );
        $bg_dir        = $this->get_option( 'bg_directory' );
        $upload_dir    = wp_upload_dir();
        $generated_dir = trailingslashit( $upload_dir['basedir'] ) . SCAPTCHA_UPLOAD_SUBDIR . '/generated';

        if ( ! wp_mkdir_p( $generated_dir ) ) {
            return array();
        }

        $digits = $this->build_digits( $code_length );
        $code   = implode( '', $digits );

        $bg_file = $this->get_random_image_from_dir( $bg_dir );
        if ( ! $bg_file ) {
            return array();
        }

        $canvas = imagecreatefrompng( $bg_file );
        imagealphablending( $canvas, true );
        imagesavealpha( $canvas, true );

        $positions = $this->calculate_positions( $canvas, $code_length );

        foreach ( $digits as $index => $digit ) {
            $digit_file = $this->get_digit_file( $digit_dir, $digit );
            if ( ! $digit_file ) {
                continue;
            }

            $digit_img = imagecreatefrompng( $digit_file );
            imagealphablending( $digit_img, true );
            imagesavealpha( $digit_img, true );

            $pos_x = $positions[ $index ];
            $pos_y = $this->calculate_vertical_center( $canvas, $digit_img );

            imagecopy( $canvas, $digit_img, $pos_x, $pos_y, 0, 0, imagesx( $digit_img ), imagesy( $digit_img ) );
            imagedestroy( $digit_img );
        }

        $image_name = 'scaptcha-' . wp_generate_password( 12, false ) . '.png';
        $file_path  = trailingslashit( $generated_dir ) . $image_name;

        imagepng( $canvas, $file_path );
        imagedestroy( $canvas );

        $this->store_captcha( $image_name, $code );

        return array(
            'image_url' => trailingslashit( $upload_dir['baseurl'] ) . SCAPTCHA_UPLOAD_SUBDIR . '/generated/' . $image_name,
            'token'     => $this->get_last_insert_id(),
        );
    }

    private function build_digits( $length ) {
        $digits = array();

        for ( $i = 0; $i < $length; $i++ ) {
            $digits[] = wp_rand( 0, 9 );
        }

        return $digits;
    }

    private function get_digit_file( $digit_dir, $digit ) {
        $folders = glob( trailingslashit( $digit_dir ) . '*', GLOB_ONLYDIR );

        if ( empty( $folders ) ) {
            return null;
        }

        $folder = $folders[ array_rand( $folders ) ];
        $file   = trailingslashit( $folder ) . $digit . '.png';

        return file_exists( $file ) ? $file : null;
    }

    private function get_random_image_from_dir( $dir ) {
        $files = glob( trailingslashit( $dir ) . '*.png' );

        if ( empty( $files ) ) {
            return null;
        }

        return $files[ array_rand( $files ) ];
    }

    private function calculate_positions( $canvas, $length ) {
        $width           = imagesx( $canvas );
        $padding         = 10;
        $available_width = $width - ( 2 * $padding );
        $gap             = floor( $available_width / $length );

        $positions = array();

        for ( $i = 0; $i < $length; $i++ ) {
            $positions[] = $padding + ( $i * $gap );
        }

        return $positions;
    }

    private function calculate_vertical_center( $canvas, $digit_img ) {
        $canvas_height = imagesy( $canvas );
        $digit_height  = imagesy( $digit_img );

        return (int) max( 0, ( $canvas_height - $digit_height ) / 2 );
    }

    private function store_captcha( $image_name, $code ) {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            array(
                'image_name' => $image_name,
                'code'       => $code,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s' )
        );
    }

    private function get_last_insert_id() {
        global $wpdb;

        return (int) $wpdb->insert_id;
    }

    public function validate( $token, $input ) {
        global $wpdb;

        $token = absint( $token );
        $input = trim( (string) $input );

        if ( empty( $token ) || '' === $input ) {
            return false;
        }

        $record = $wpdb->get_row( $wpdb->prepare( "SELECT code FROM {$this->table_name} WHERE id = %d", $token ), ARRAY_A );

        if ( empty( $record ) ) {
            return false;
        }

        return hash_equals( $record['code'], $input );
    }
}

