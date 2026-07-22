<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_Shortcodes {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'falci_basvuru_formu', array( $this, 'shortcode_form' ) );
		add_shortcode( 'falci_listesi', array( $this, 'shortcode_liste' ) );
		add_shortcode( 'falci_profil', array( $this, 'shortcode_profil' ) );
	}

	public function shortcode_form( $atts ) {
		return Falci_Form::get_instance()->render_form();
	}

	public function shortcode_liste( $atts ) {
		$atts = shortcode_atts( array(
			'uzmanlik' => '',
			'limit'    => 12,
			'columns'  => 3,
		), $atts, 'falci_listesi' );

		$uzmanlik = sanitize_key( $atts['uzmanlik'] );
		$limit    = absint( $atts['limit'] );
		$columns  = min( 4, max( 1, absint( $atts['columns'] ) ) );

		ob_start();
		include FALCI_PLUGIN_DIR . 'templates/liste-falcilar.php';
		return ob_get_clean();
	}

	public function shortcode_profil( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'falci_profil' );
		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}

		ob_start();
		include FALCI_PLUGIN_DIR . 'templates/single-falci-embed.php';
		return ob_get_clean();
	}
}
