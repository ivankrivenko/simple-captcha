<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
exit;
}

$options = get_option( SCAPTCHA_OPTION_NAME );

global $wpdb;
$table_name = $wpdb->prefix . 'scaptcha_images';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

delete_option( SCAPTCHA_OPTION_NAME );

$upload_dir = wp_upload_dir();
$base_dir   = trailingslashit( $upload_dir['basedir'] ) . SCAPTCHA_UPLOAD_SUBDIR;

if ( is_dir( $base_dir ) ) {
// Remove generated files only to avoid deleting user assets.
$generated = trailingslashit( $base_dir ) . 'generated';
if ( is_dir( $generated ) ) {
foreach ( glob( $generated . '/*.png' ) as $file ) {
@unlink( $file );
}
@rmdir( $generated );
}
}
