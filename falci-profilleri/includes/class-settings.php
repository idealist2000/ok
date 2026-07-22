<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_Settings {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_submenu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_submenu() {
		add_submenu_page(
			'edit.php?post_type=falci',
			'Falcı Eklenti Ayarları',
			'Ayarlar',
			'manage_options',
			'falci-ayarlar',
			array( $this, 'render_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'falci_settings_group',
			'falci_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	public function sanitize( $input ) {
		$clean = array();

		$clean['ai_provider'] = in_array( $input['ai_provider'] ?? '', array( 'openai', 'claude' ), true )
			? $input['ai_provider']
			: 'openai';

		$clean['api_key']        = sanitize_text_field( $input['api_key'] ?? '' );
		$clean['auto_approve']   = ! empty( $input['auto_approve'] ) ? '1' : '0';
		$clean['bildirim_email'] = sanitize_email( $input['bildirim_email'] ?? '' );

		return $clean;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = get_option( 'falci_settings', array(
			'ai_provider'    => 'openai',
			'api_key'        => '',
			'auto_approve'   => '1',
			'bildirim_email' => get_option( 'admin_email' ),
		) );
		include FALCI_PLUGIN_DIR . 'templates/admin-settings.php';
	}
}
