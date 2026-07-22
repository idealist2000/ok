<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_Post_Type {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type() {
		$labels = array(
			'name'               => 'Falcılar',
			'singular_name'      => 'Falcı',
			'add_new'            => 'Yeni Falcı Ekle',
			'add_new_item'       => 'Yeni Falcı Ekle',
			'edit_item'          => 'Falcıyı Düzenle',
			'new_item'           => 'Yeni Falcı',
			'view_item'          => 'Falcıyı Görüntüle',
			'search_items'       => 'Falcı Ara',
			'not_found'          => 'Falcı bulunamadı',
			'not_found_in_trash' => 'Çöp kutusunda falcı bulunamadı',
			'menu_name'          => 'Falcılar',
			'all_items'          => 'Tüm Falcılar',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'falci', 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => 'falcilar',
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-star-filled',
			'supports'           => array( 'title' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'falci', $args );
	}

	/**
	 * Returns all supported expertise areas.
	 *
	 * @return array  key => Turkish label
	 */
	public static function get_uzmanlik_alanlari() {
		return array(
			'kahve_fali'   => 'Kahve Falı',
			'tarot'        => 'Tarot',
			'el_fali'      => 'El Falı',
			'yildizname'   => 'Yıldızname',
			'katina'       => 'Katina',
			'ruya_tabiri'  => 'Rüya Tabiri',
			'numeroloji'   => 'Numeroloji',
			'kristal_kure' => 'Kristal Küre',
			'astroloji'    => 'Astroloji',
			'geomancy'     => 'Geomancy (Toprak Falı)',
		);
	}

	/**
	 * Returns only published (approved) fortune tellers for a query.
	 *
	 * @param string $uzmanlik  Optional expertise key filter.
	 * @param int    $limit
	 * @param int    $paged
	 * @return WP_Query
	 */
	public static function query_falcilar( $uzmanlik = '', $limit = 12, $paged = 1 ) {
		$args = array(
			'post_type'      => 'falci',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_falci_durum',
					'value' => 'yayinda',
				),
			),
		);

		if ( $uzmanlik ) {
			$args['meta_query'][] = array(
				'key'     => '_falci_uzmanliklar',
				'value'   => serialize( $uzmanlik ),
				'compare' => 'LIKE',
			);
		}

		return new WP_Query( $args );
	}
}
