<?php
/**
 * Plugin Name: Falcı Profilleri
 * Plugin URI:  https://github.com/idealist2000/ok
 * Description: Falcı profilleri yönetim sistemi — başvuru formu, yapay zeka onay kontrolü, admin paneli, dizin ve profil sayfaları.
 * Version:     1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author:      Falcı Profilleri
 * License:     GPL v2 or later
 * Text Domain: falci-profilleri
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FALCI_VERSION', '1.0.0' );
define( 'FALCI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FALCI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FALCI_PLUGIN_FILE', __FILE__ );
define( 'FALCI_PHONE', '+905393573407' );
define( 'FALCI_PHONE_DISPLAY', '+90 539 357 34 07' );
define( 'FALCI_WHATSAPP_BASE', 'https://wa.me/905393573407' );

require_once FALCI_PLUGIN_DIR . 'includes/class-post-type.php';
require_once FALCI_PLUGIN_DIR . 'includes/class-admin.php';
require_once FALCI_PLUGIN_DIR . 'includes/class-form.php';
require_once FALCI_PLUGIN_DIR . 'includes/class-ai-checker.php';
require_once FALCI_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once FALCI_PLUGIN_DIR . 'includes/class-settings.php';

final class Falci_Profilleri {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( FALCI_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( FALCI_PLUGIN_FILE, array( $this, 'deactivate' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		Falci_Post_Type::get_instance();
		Falci_Admin::get_instance();
		Falci_Form::get_instance();
		Falci_Shortcodes::get_instance();
		Falci_Settings::get_instance();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Single template override
		add_filter( 'single_template', array( $this, 'single_falci_template' ) );
		add_filter( 'archive_template', array( $this, 'archive_falci_template' ) );
	}

	public function activate() {
		Falci_Post_Type::get_instance()->register_post_type();
		flush_rewrite_rules();

		if ( ! get_option( 'falci_settings' ) ) {
			update_option( 'falci_settings', array(
				'ai_provider'    => 'openai',
				'api_key'        => '',
				'auto_approve'   => '1',
				'bildirim_email' => get_option( 'admin_email' ),
			) );
		}
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'falci-frontend',
			FALCI_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			FALCI_VERSION
		);
		wp_enqueue_script(
			'falci-frontend',
			FALCI_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			FALCI_VERSION,
			true
		);
		wp_localize_script( 'falci-frontend', 'falciData', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'falci_form_nonce' ),
		) );
	}

	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		if ( 'falci' !== $screen->post_type && 'falci_page_falci-ayarlar' !== $screen->id ) {
			return;
		}
		wp_enqueue_style(
			'falci-admin',
			FALCI_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			FALCI_VERSION
		);
		wp_enqueue_media();
		wp_enqueue_script(
			'falci-admin',
			FALCI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			FALCI_VERSION,
			true
		);
	}

	public function single_falci_template( $template ) {
		if ( is_singular( 'falci' ) ) {
			$plugin_template = FALCI_PLUGIN_DIR . 'templates/single-falci.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
	}

	public function archive_falci_template( $template ) {
		if ( is_post_type_archive( 'falci' ) ) {
			$plugin_template = FALCI_PLUGIN_DIR . 'templates/archive-falci.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}
		return $template;
	}
}

Falci_Profilleri::get_instance();
