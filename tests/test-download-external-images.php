<?php
/**
 * Minimal CLI tests for external image download behavior.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HARIKRUTFIWU_OPTIONS', 'harikrutfiwu_options' );
define( 'HARIKRUTFIWU_WCGALLARY', '_harikrutfiwu_wcgallary' );
define( 'HARIKRUTFIWU_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'HARIKRUTFIWU_TESTING', true );

$GLOBALS['wp_options']       = array();
$GLOBALS['wp_post_meta']     = array();
$GLOBALS['wp_posts']         = array();
$GLOBALS['sideload_calls']   = array();
$GLOBALS['sideload_result']  = 44;
$GLOBALS['registered_hooks'] = array();
$GLOBALS['safe_redirect_to'] = '';

function is_admin() {
	return true;
}

function add_action( $hook, $callback ) {
	$GLOBALS['registered_hooks'][ $hook ] = $callback;
}

function add_filter() {}
function add_meta_box() {}
function add_options_page() {}
function register_setting() {}
function add_settings_section() {}
function add_settings_field() {}
function wp_enqueue_style() {}
function wp_register_script() {}
function wp_localize_script() {}
function wp_enqueue_script() {}
function __return_false() {
	return false;
}

function current_user_can() {
	return true;
}

function post_type_supports() {
	return true;
}

function get_post_types() {
	return array( 'post' => 'post', 'page' => 'page' );
}

function wp_verify_nonce() {
	return true;
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_text_field( $value ) {
	return trim( (string) $value );
}

function __( $text ) {
	return $text;
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_unslash( $value ) {
	return $value;
}

function esc_url_raw( $value ) {
	return filter_var( $value, FILTER_SANITIZE_URL );
}

function get_post_type( $post_id ) {
	return isset( $GLOBALS['wp_posts'][ $post_id ] ) ? $GLOBALS['wp_posts'][ $post_id ]->post_type : 'post';
}

function get_post_meta( $post_id, $key, $single = false ) {
	if ( ! isset( $GLOBALS['wp_post_meta'][ $post_id ][ $key ] ) ) {
		return $single ? '' : array();
	}

	return $GLOBALS['wp_post_meta'][ $post_id ][ $key ];
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['wp_post_meta'][ $post_id ][ $key ] = $value;
	return true;
}

function delete_post_meta( $post_id, $key ) {
	unset( $GLOBALS['wp_post_meta'][ $post_id ][ $key ] );
	return true;
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['wp_options'] ) ? $GLOBALS['wp_options'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['wp_options'][ $key ] = $value;
	return true;
}

function get_posts() {
	return array();
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( $args );
}

function wp_safe_redirect( $url ) {
	$GLOBALS['safe_redirect_to'] = $url;
}

function wp_die( $message ) {
	throw new Exception( $message );
}

function media_sideload_image( $url, $post_id, $description = null, $return_type = 'html' ) {
	$GLOBALS['sideload_calls'][] = compact( 'url', 'post_id', 'description', 'return_type' );
	return $GLOBALS['sideload_result'];
}

function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

function set_post_thumbnail( $post_id, $attachment_id ) {
	update_post_meta( $post_id, '_thumbnail_id', (int) $attachment_id );
	return true;
}

function wp_update_post( $postarr ) {
	update_post_meta( $postarr['ID'], '_wp_update_post_payload', $postarr );
	return $postarr['ID'];
}

function wp_schedule_single_event() {}
function wp_next_scheduled() {
	return false;
}

class WP_Error {
	private $message;

	public function __construct( $code, $message ) {
		$this->message = $message;
	}

	public function get_error_message() {
		return $this->message;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-harikrutfiwu-admin.php';

function reset_state() {
	$GLOBALS['wp_options']      = array();
	$GLOBALS['wp_post_meta']    = array();
	$GLOBALS['wp_posts']        = array();
	$GLOBALS['sideload_calls']  = array();
	$GLOBALS['sideload_result'] = 44;
	$GLOBALS['safe_redirect_to']  = '';
	$_POST                       = array();
	$_GET                        = array();
}

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new Exception( $message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

function save_external_url( $admin, $post_id, $url, $alt = '' ) {
	$GLOBALS['wp_posts'][ $post_id ] = (object) array(
		'ID'        => $post_id,
		'post_type' => 'post',
	);
	$_POST = array(
		'harikrutfiwu_url'           => $url,
		'harikrutfiwu_alt'           => $alt,
		'harikrutfiwu_img_url_nonce' => 'ok',
	);
	$admin->harikrutfiwu_save_image_url_data( $post_id, $GLOBALS['wp_posts'][ $post_id ] );
}

$tests = array(
	'default setting keeps the original external-url behavior' => function() {
		reset_state();
		$admin = new HARIKRUTFIWU_Admin();

		save_external_url( $admin, 101, 'https://cdn.example.test/image.jpg', 'Alt' );

		assert_same( 'https://cdn.example.test/image.jpg', get_post_meta( 101, '_harikrutfiwu_url', true ), 'External URL should remain in legacy meta.' );
		assert_same( '', get_post_meta( 101, '_thumbnail_id', true ), 'Thumbnail should not be set when download option is off.' );
		assert_same( 0, count( $GLOBALS['sideload_calls'] ), 'Downloader should not run by default.' );
	},
	'enabled setting downloads image and sets real featured image' => function() {
		reset_state();
		update_option( HARIKRUTFIWU_OPTIONS, array( 'harikrutfiwu_download_external_images' => '1' ) );
		$admin = new HARIKRUTFIWU_Admin();

		save_external_url( $admin, 102, 'https://cdn.example.test/image.jpg', 'Alt text' );

		assert_same( 44, get_post_meta( 102, '_thumbnail_id', true ), 'Downloaded attachment should become the featured image.' );
		assert_same( 'https://cdn.example.test/image.jpg', get_post_meta( 102, '_harikrutfiwu_url', true ), 'Legacy URL meta should stay compatible.' );
		assert_same( 'https://cdn.example.test/image.jpg', get_post_meta( 102, '_harikrutfiwu_downloaded_source_url', true ), 'Original source URL should be tracked.' );
		assert_same( 44, get_post_meta( 102, '_harikrutfiwu_downloaded_attachment_id', true ), 'Downloaded attachment ID should be tracked.' );
		assert_same( 1, count( $GLOBALS['sideload_calls'] ), 'Downloader should run once.' );
		assert_same( 'id', $GLOBALS['sideload_calls'][0]['return_type'], 'Downloader should request attachment id.' );
	},
	'download failure keeps external image meta as fallback' => function() {
		reset_state();
		update_option( HARIKRUTFIWU_OPTIONS, array( 'harikrutfiwu_download_external_images' => '1' ) );
		$GLOBALS['sideload_result'] = new WP_Error( 'download_failed', 'No file' );
		$admin                      = new HARIKRUTFIWU_Admin();

		save_external_url( $admin, 103, 'https://cdn.example.test/broken.jpg' );

		assert_same( '', get_post_meta( 103, '_thumbnail_id', true ), 'Thumbnail should stay empty on failed download.' );
		assert_same( 'https://cdn.example.test/broken.jpg', get_post_meta( 103, '_harikrutfiwu_url', true ), 'External URL fallback should remain.' );
		assert_same( 'No file', get_post_meta( 103, '_harikrutfiwu_download_error', true ), 'Download error should be stored for diagnosis.' );
	},
	'manual batch action redirects with completion status' => function() {
		reset_state();
		update_option( HARIKRUTFIWU_OPTIONS, array( 'harikrutfiwu_download_external_images' => '1' ) );
		$admin = new HARIKRUTFIWU_Admin();
		$_GET  = array( 'harikrutfiwu_manual_download_nonce' => 'ok' );

		$admin->handle_manual_download_batch();

		assert_same( 'https://example.test/wp-admin/options-general.php?page=harikrutfiwu&harikrutfiwu_manual_download=complete', $GLOBALS['safe_redirect_to'], 'Manual action should redirect back to settings with status.' );
	},
);

$failures = 0;
foreach ( $tests as $name => $test ) {
	try {
		$test();
		echo "[PASS] {$name}\n";
	} catch ( Throwable $exception ) {
		$failures++;
		echo "[FAIL] {$name}: {$exception->getMessage()}\n";
	}
}

exit( $failures > 0 ? 1 : 0 );
