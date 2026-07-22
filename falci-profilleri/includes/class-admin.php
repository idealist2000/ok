<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_falci', array( $this, 'save_meta_boxes' ), 10, 2 );

		add_filter( 'manage_falci_posts_columns', array( $this, 'custom_columns' ) );
		add_action( 'manage_falci_posts_custom_column', array( $this, 'custom_column_content' ), 10, 2 );
		add_filter( 'manage_edit-falci_sortable_columns', array( $this, 'sortable_columns' ) );

		add_action( 'admin_init', array( $this, 'handle_quick_status_change' ) );
		add_action( 'restrict_manage_posts', array( $this, 'filter_by_status_dropdown' ) );
		add_action( 'parse_query', array( $this, 'apply_status_filter' ) );

		add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );

		// Remove default publish metabox fields that confuse the workflow
		add_action( 'admin_head-post.php', array( $this, 'hide_publish_box_elements' ) );
		add_action( 'admin_head-post-new.php', array( $this, 'hide_publish_box_elements' ) );
	}

	public function hide_publish_box_elements() {
		global $post;
		if ( ! $post || 'falci' !== $post->post_type ) {
			return;
		}
		echo '<style>#misc-publishing-actions,#minor-publishing-actions{display:none}</style>';
	}

	// ------------------------------------------------------------------ Meta boxes

	public function add_meta_boxes() {
		add_meta_box(
			'falci_profil_bilgileri',
			'Falcı Profil Bilgileri',
			array( $this, 'render_profil_meta_box' ),
			'falci',
			'normal',
			'high'
		);

		add_meta_box(
			'falci_durum_bilgisi',
			'Durum ve Onay',
			array( $this, 'render_durum_meta_box' ),
			'falci',
			'side',
			'high'
		);
	}

	public function render_profil_meta_box( $post ) {
		wp_nonce_field( 'falci_meta_nonce', 'falci_meta_nonce_field' );
		$meta             = $this->get_all_meta( $post->ID );
		$uzmanliklar_list = Falci_Post_Type::get_uzmanlik_alanlari();
		$selected         = get_post_meta( $post->ID, '_falci_uzmanliklar', true );
		$selected         = is_array( $selected ) ? $selected : array();
		include FALCI_PLUGIN_DIR . 'templates/admin-meta-box.php';
	}

	public function render_durum_meta_box( $post ) {
		$durum       = get_post_meta( $post->ID, '_falci_durum', true ) ?: 'beklemede';
		$ret_gerekce = get_post_meta( $post->ID, '_falci_ret_gerekce', true );
		$ai_sonuc    = get_post_meta( $post->ID, '_falci_ai_kontrol_sonucu', true );
		include FALCI_PLUGIN_DIR . 'templates/admin-durum-box.php';
	}

	public function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['falci_meta_nonce_field'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['falci_meta_nonce_field'] ) ), 'falci_meta_nonce' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Scalar fields
		$scalar_fields = array(
			'_falci_ad_soyad'         => 'sanitize_text_field',
			'_falci_rumuz'            => 'sanitize_text_field',
			'_falci_deneyim_yili'     => 'absint',
			'_falci_biyografi'        => 'sanitize_textarea_field',
			'_falci_calisma_saatleri' => 'sanitize_textarea_field',
			'_falci_seans_ucreti'     => 'sanitize_text_field',
			'_falci_sehir'            => 'sanitize_text_field',
			'_falci_tanitim'          => 'sanitize_textarea_field',
			'_falci_ret_gerekce'      => 'sanitize_textarea_field',
		);

		foreach ( $scalar_fields as $meta_key => $fn ) {
			if ( isset( $_POST[ $meta_key ] ) ) {
				$value = wp_unslash( $_POST[ $meta_key ] );
				update_post_meta( $post_id, $meta_key, $fn( $value ) );
			}
		}

		// Expertise areas (array)
		$valid     = array_keys( Falci_Post_Type::get_uzmanlik_alanlari() );
		$submitted = isset( $_POST['_falci_uzmanliklar'] ) ? (array) $_POST['_falci_uzmanliklar'] : array();
		$clean     = array_intersect( array_map( 'sanitize_key', $submitted ), $valid );
		update_post_meta( $post_id, '_falci_uzmanliklar', $clean );

		// Profile photo attachment ID
		if ( isset( $_POST['_falci_foto_id'] ) ) {
			update_post_meta( $post_id, '_falci_foto_id', absint( $_POST['_falci_foto_id'] ) );
		}

		// Status change — also updates post_status
		if ( isset( $_POST['_falci_durum'] ) ) {
			$new_durum = sanitize_key( $_POST['_falci_durum'] );
			if ( in_array( $new_durum, array( 'yayinda', 'beklemede', 'reddedildi' ), true ) ) {
				update_post_meta( $post_id, '_falci_durum', $new_durum );
				$this->sync_post_status( $post_id, $new_durum );
			}
		}

		// Update post title to rumuz
		$rumuz = sanitize_text_field( wp_unslash( $_POST['_falci_rumuz'] ?? '' ) );
		if ( $rumuz && $rumuz !== $post->post_title ) {
			remove_action( 'save_post_falci', array( $this, 'save_meta_boxes' ), 10 );
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $rumuz ) );
			add_action( 'save_post_falci', array( $this, 'save_meta_boxes' ), 10, 2 );
		}
	}

	// ------------------------------------------------------------------ Columns

	public function custom_columns( $columns ) {
		return array(
			'cb'            => $columns['cb'],
			'title'         => 'Rumuz',
			'foto'          => 'Fotoğraf',
			'ad_soyad'      => 'Ad Soyad',
			'uzmanliklar'   => 'Uzmanlıklar',
			'sehir'         => 'Şehir',
			'durum'         => 'Durum',
			'basvuru_tarihi'=> 'Başvuru Tarihi',
			'islemler'      => 'Hızlı İşlem',
		);
	}

	public function custom_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'foto':
				$foto_id = (int) get_post_meta( $post_id, '_falci_foto_id', true );
				if ( $foto_id ) {
					echo wp_get_attachment_image( $foto_id, array( 40, 40 ), false, array( 'style' => 'border-radius:50%;width:40px;height:40px;object-fit:cover;' ) );
				} else {
					echo '<span style="color:#aaa;font-size:24px;">&#9734;</span>';
				}
				break;

			case 'ad_soyad':
				echo esc_html( get_post_meta( $post_id, '_falci_ad_soyad', true ) );
				break;

			case 'uzmanliklar':
				$uzmanliklar = (array) get_post_meta( $post_id, '_falci_uzmanliklar', true );
				$tum         = Falci_Post_Type::get_uzmanlik_alanlari();
				$isimler     = array_map( function( $k ) use ( $tum ) {
					return isset( $tum[ $k ] ) ? $tum[ $k ] : $k;
				}, $uzmanliklar );
				echo esc_html( implode( ', ', $isimler ) );
				break;

			case 'sehir':
				echo esc_html( get_post_meta( $post_id, '_falci_sehir', true ) );
				break;

			case 'durum':
				$durum = get_post_meta( $post_id, '_falci_durum', true ) ?: 'beklemede';
				$map   = array(
					'yayinda'    => '<span class="falci-badge yayinda">&#10003; Yayında</span>',
					'beklemede'  => '<span class="falci-badge beklemede">&#8987; Beklemede</span>',
					'reddedildi' => '<span class="falci-badge reddedildi">&#10007; Reddedildi</span>',
				);
				echo isset( $map[ $durum ] ) ? wp_kses_post( $map[ $durum ] ) : esc_html( $durum );
				break;

			case 'basvuru_tarihi':
				$tarih = get_post_meta( $post_id, '_falci_basvuru_tarihi', true );
				echo $tarih
					? esc_html( date_i18n( 'd.m.Y H:i', strtotime( $tarih ) ) )
					: esc_html( get_the_date( 'd.m.Y H:i', $post_id ) );
				break;

			case 'islemler':
				$durum = get_post_meta( $post_id, '_falci_durum', true ) ?: 'beklemede';
				$nonce = wp_create_nonce( 'falci_hizli_durum' );
				if ( 'yayinda' !== $durum ) {
					echo '<a href="' . esc_url( admin_url( "admin.php?action=falci_onayla&post_id={$post_id}&_nonce={$nonce}" ) ) . '" class="button button-small button-primary">Onayla</a> ';
				}
				if ( 'reddedildi' !== $durum ) {
					echo '<a href="' . esc_url( admin_url( "admin.php?action=falci_reddet&post_id={$post_id}&_nonce={$nonce}" ) ) . '" class="button button-small" onclick="return confirm(\'Bu falcıyı reddetmek istediğinizden emin misiniz?\')">Reddet</a>';
				}
				break;
		}
	}

	public function sortable_columns( $columns ) {
		$columns['durum'] = '_falci_durum';
		$columns['sehir'] = '_falci_sehir';
		return $columns;
	}

	// ------------------------------------------------------------------ Quick actions

	public function handle_quick_status_change() {
		if ( ! isset( $_GET['action'], $_GET['post_id'], $_GET['_nonce'] ) ) {
			return;
		}

		$action  = sanitize_key( $_GET['action'] );
		$post_id = absint( $_GET['post_id'] );

		if ( ! in_array( $action, array( 'falci_onayla', 'falci_reddet' ), true ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ), 'falci_hizli_durum' ) ) {
			wp_die( 'Güvenlik hatası.' );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'Yetkiniz yok.' );
		}

		if ( 'falci_onayla' === $action ) {
			update_post_meta( $post_id, '_falci_durum', 'yayinda' );
			$this->sync_post_status( $post_id, 'yayinda' );
			$msg = urlencode( 'Falcı profili yayınlandı.' );
		} else {
			update_post_meta( $post_id, '_falci_durum', 'reddedildi' );
			$this->sync_post_status( $post_id, 'reddedildi' );
			$msg = urlencode( 'Falcı profili reddedildi.' );
		}

		wp_safe_redirect( admin_url( "edit.php?post_type=falci&falci_notice={$msg}" ) );
		exit;
	}

	// ------------------------------------------------------------------ Filtering

	public function filter_by_status_dropdown( $post_type ) {
		if ( 'falci' !== $post_type ) {
			return;
		}
		$current = isset( $_GET['falci_durum'] ) ? sanitize_key( $_GET['falci_durum'] ) : '';
		$options = array(
			''           => 'Tüm Durumlar',
			'yayinda'    => 'Yayında',
			'beklemede'  => 'Beklemede',
			'reddedildi' => 'Reddedildi',
		);
		echo '<select name="falci_durum">';
		foreach ( $options as $val => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $val ),
				selected( $current, $val, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	public function apply_status_filter( $query ) {
		global $pagenow;
		if ( ! is_admin() || 'edit.php' !== $pagenow ) {
			return;
		}
		if ( empty( $_GET['post_type'] ) || 'falci' !== $_GET['post_type'] ) {
			return;
		}
		if ( empty( $_GET['falci_durum'] ) ) {
			return;
		}

		$durum      = sanitize_key( $_GET['falci_durum'] );
		$meta_query = array();

		if ( 'beklemede' === $durum ) {
			$meta_query = array(
				'relation' => 'OR',
				array( 'key' => '_falci_durum', 'value' => 'beklemede' ),
				array( 'key' => '_falci_durum', 'compare' => 'NOT EXISTS' ),
			);
		} else {
			$meta_query = array(
				array( 'key' => '_falci_durum', 'value' => $durum ),
			);
		}

		$query->set( 'meta_query', $meta_query );
		$query->set( 'post_status', array( 'publish', 'draft', 'pending' ) );
	}

	// ------------------------------------------------------------------ Notice

	public function show_admin_notice() {
		if ( ! isset( $_GET['falci_notice'] ) ) {
			return;
		}
		$message = sanitize_text_field( urldecode( $_GET['falci_notice'] ) );
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	// ------------------------------------------------------------------ Helpers

	private function sync_post_status( $post_id, $falci_durum ) {
		remove_action( 'save_post_falci', array( $this, 'save_meta_boxes' ), 10 );
		wp_update_post( array(
			'ID'          => $post_id,
			'post_status' => ( 'yayinda' === $falci_durum ) ? 'publish' : 'draft',
		) );
		add_action( 'save_post_falci', array( $this, 'save_meta_boxes' ), 10, 2 );
	}

	private function get_all_meta( $post_id ) {
		$keys = array(
			'_falci_ad_soyad',
			'_falci_rumuz',
			'_falci_foto_id',
			'_falci_uzmanliklar',
			'_falci_deneyim_yili',
			'_falci_biyografi',
			'_falci_calisma_saatleri',
			'_falci_seans_ucreti',
			'_falci_sehir',
			'_falci_tanitim',
		);
		$meta = array();
		foreach ( $keys as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}
		return $meta;
	}
}
