<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_Form {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_falci_basvuru_gonder', array( $this, 'handle_submission' ) );
		add_action( 'wp_ajax_nopriv_falci_basvuru_gonder', array( $this, 'handle_submission' ) );
	}

	public function render_form() {
		$uzmanliklar = Falci_Post_Type::get_uzmanlik_alanlari();
		ob_start();
		include FALCI_PLUGIN_DIR . 'templates/form-basvuru.php';
		return ob_get_clean();
	}

	public function handle_submission() {
		// Nonce check
		if ( ! check_ajax_referer( 'falci_form_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Güvenlik doğrulaması başarısız.' ) );
			return;
		}

		// Honeypot check
		if ( ! empty( $_POST['hp_website'] ) ) {
			wp_send_json_error( array( 'message' => 'Spam algılandı.' ) );
			return;
		}

		// Validate
		$errors = $this->validate( $_POST );
		if ( ! empty( $errors ) ) {
			wp_send_json_error( array( 'message' => implode( '<br>', $errors ) ) );
			return;
		}

		// Sanitize
		$data = $this->sanitize( $_POST );

		// File upload
		$foto_id = 0;
		if ( ! empty( $_FILES['profil_foto']['tmp_name'] ) ) {
			$upload = $this->upload_photo( $_FILES['profil_foto'] );
			if ( is_wp_error( $upload ) ) {
				wp_send_json_error( array( 'message' => 'Fotoğraf hatası: ' . $upload->get_error_message() ) );
				return;
			}
			$foto_id = $upload;
		}

		// Create draft post (title = rumuz)
		$post_id = wp_insert_post( array(
			'post_title'   => $data['rumuz'],
			'post_type'    => 'falci',
			'post_status'  => 'draft',
			'post_content' => '',
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Başvuru kaydedilemedi. Lütfen tekrar deneyin.' ) );
			return;
		}

		// Save meta
		$this->save_meta( $post_id, $data, $foto_id );

		// AI content check
		$settings      = get_option( 'falci_settings', array() );
		$auto_approve  = ! empty( $settings['auto_approve'] );
		$has_api_key   = ! empty( $settings['api_key'] );

		if ( $auto_approve && $has_api_key ) {
			$checker   = new Falci_AI_Checker();
			$ai_result = $checker->check_content( $data );

			if ( $ai_result['onaylandi'] ) {
				update_post_meta( $post_id, '_falci_durum', 'yayinda' );
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
				update_post_meta( $post_id, '_falci_ai_kontrol_sonucu', 'Onaylandı: ' . $ai_result['mesaj'] );
			} else {
				update_post_meta( $post_id, '_falci_durum', 'reddedildi' );
				update_post_meta( $post_id, '_falci_ret_gerekce', $ai_result['mesaj'] );
				update_post_meta( $post_id, '_falci_ai_kontrol_sonucu', 'Reddedildi: ' . $ai_result['mesaj'] );
			}
		}

		// Notification email
		$this->send_notification( $post_id, $data );

		$final_durum = get_post_meta( $post_id, '_falci_durum', true );

		if ( 'yayinda' === $final_durum ) {
			$msg = 'Başvurunuz onaylandı ve profiliniz yayına alındı! Teşekkür ederiz.';
		} elseif ( 'reddedildi' === $final_durum ) {
			$ret = get_post_meta( $post_id, '_falci_ret_gerekce', true );
			$msg = 'Başvurunuz reddedildi. Gerekçe: ' . $ret;
		} else {
			$msg = 'Başvurunuz alındı ve incelemeye alındı. En kısa sürede size bilgi verilecektir.';
		}

		wp_send_json_success( array( 'message' => $msg, 'durum' => $final_durum ) );
	}

	// ------------------------------------------------------------------ Validation

	private function validate( $data ) {
		$errors = array();

		if ( empty( $data['ad_soyad'] ) || mb_strlen( trim( $data['ad_soyad'] ) ) < 3 ) {
			$errors[] = 'Ad soyad en az 3 karakter olmalıdır.';
		}

		if ( empty( $data['rumuz'] ) || mb_strlen( trim( $data['rumuz'] ) ) < 2 ) {
			$errors[] = 'Rumuz/mahlas en az 2 karakter olmalıdır.';
		}

		if ( empty( $data['uzmanliklar'] ) || ! is_array( $data['uzmanliklar'] ) ) {
			$errors[] = 'En az bir uzmanlık alanı seçmelisiniz.';
		}

		if ( ! isset( $data['deneyim_yili'] ) || ! ctype_digit( (string) $data['deneyim_yili'] ) ) {
			$errors[] = 'Geçerli bir deneyim yılı giriniz.';
		}

		$biyografi = isset( $data['biyografi'] ) ? trim( $data['biyografi'] ) : '';
		if ( mb_strlen( $biyografi ) < 300 ) {
			$errors[] = 'Biyografi en az 300 karakter olmalıdır (şu an: ' . mb_strlen( $biyografi ) . ' karakter).';
		}

		// Block phone/link/social in biography
		if ( $this->contains_contact_info( $biyografi ) ) {
			$errors[] = 'Biyografi içinde telefon numarası, web sitesi veya iletişim bilgisi paylaşılamaz.';
		}

		if ( empty( $data['calisma_saatleri'] ) ) {
			$errors[] = 'Çalışma saatleri boş bırakılamaz.';
		}

		if ( empty( $data['seans_ucreti'] ) ) {
			$errors[] = 'Seans ücreti boş bırakılamaz.';
		}

		if ( empty( $data['sehir'] ) ) {
			$errors[] = 'Şehir boş bırakılamaz.';
		}

		$tanitim = isset( $data['tanitim'] ) ? trim( $data['tanitim'] ) : '';
		if ( $this->contains_contact_info( $tanitim ) ) {
			$errors[] = 'Tanıtım metninde iletişim bilgisi paylaşılamaz.';
		}

		return $errors;
	}

	private function contains_contact_info( $text ) {
		// Phone numbers, URLs, @mentions, email addresses
		$pattern = '/(\+?\d[\d\s\-\(\)\.]{7,}|https?:\/\/\S+|www\.\S+|\b[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}|@\w{2,})/i';
		return (bool) preg_match( $pattern, $text );
	}

	// ------------------------------------------------------------------ Sanitize

	private function sanitize( $data ) {
		$valid_uzmanliklar = array_keys( Falci_Post_Type::get_uzmanlik_alanlari() );
		$submitted         = isset( $data['uzmanliklar'] ) ? (array) $data['uzmanliklar'] : array();
		$clean_uzmanliklar = array_values( array_intersect( array_map( 'sanitize_key', $submitted ), $valid_uzmanliklar ) );

		return array(
			'ad_soyad'         => sanitize_text_field( wp_unslash( $data['ad_soyad'] ?? '' ) ),
			'rumuz'            => sanitize_text_field( wp_unslash( $data['rumuz'] ?? '' ) ),
			'uzmanliklar'      => $clean_uzmanliklar,
			'deneyim_yili'     => absint( $data['deneyim_yili'] ?? 0 ),
			'biyografi'        => sanitize_textarea_field( wp_unslash( $data['biyografi'] ?? '' ) ),
			'calisma_saatleri' => sanitize_textarea_field( wp_unslash( $data['calisma_saatleri'] ?? '' ) ),
			'seans_ucreti'     => sanitize_text_field( wp_unslash( $data['seans_ucreti'] ?? '' ) ),
			'sehir'            => sanitize_text_field( wp_unslash( $data['sehir'] ?? '' ) ),
			'tanitim'          => sanitize_textarea_field( wp_unslash( $data['tanitim'] ?? '' ) ),
		);
	}

	// ------------------------------------------------------------------ Meta save

	private function save_meta( $post_id, $data, $foto_id ) {
		update_post_meta( $post_id, '_falci_ad_soyad', $data['ad_soyad'] );
		update_post_meta( $post_id, '_falci_rumuz', $data['rumuz'] );
		update_post_meta( $post_id, '_falci_uzmanliklar', $data['uzmanliklar'] );
		update_post_meta( $post_id, '_falci_deneyim_yili', $data['deneyim_yili'] );
		update_post_meta( $post_id, '_falci_biyografi', $data['biyografi'] );
		update_post_meta( $post_id, '_falci_calisma_saatleri', $data['calisma_saatleri'] );
		update_post_meta( $post_id, '_falci_seans_ucreti', $data['seans_ucreti'] );
		update_post_meta( $post_id, '_falci_sehir', $data['sehir'] );
		update_post_meta( $post_id, '_falci_tanitim', $data['tanitim'] );
		update_post_meta( $post_id, '_falci_foto_id', $foto_id );
		update_post_meta( $post_id, '_falci_durum', 'beklemede' );
		update_post_meta( $post_id, '_falci_basvuru_tarihi', current_time( 'mysql' ) );
	}

	// ------------------------------------------------------------------ File upload

	private function upload_photo( $file ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$allowed_mime = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed_mime, true ) ) {
			return new WP_Error( 'invalid_type', 'Sadece JPG, PNG, GIF ve WebP görselleri kabul edilir.' );
		}

		// 5 MB max
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			return new WP_Error( 'too_large', 'Dosya boyutu en fazla 5 MB olabilir.' );
		}

		$moved = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $moved['error'] ) ) {
			return new WP_Error( 'upload_fail', $moved['error'] );
		}

		$attach_id   = wp_insert_attachment( array(
			'guid'           => $moved['url'],
			'post_mime_type' => $moved['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file['name'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		), $moved['file'] );

		wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $moved['file'] ) );

		return $attach_id;
	}

	// ------------------------------------------------------------------ Email

	private function send_notification( $post_id, $data ) {
		$settings = get_option( 'falci_settings', array() );
		$to       = sanitize_email( $settings['bildirim_email'] ?? get_option( 'admin_email' ) );

		if ( ! is_email( $to ) ) {
			return;
		}

		$subject = sprintf( '[Falcı Başvurusu] %s', $data['rumuz'] );
		$body    = sprintf(
			"Yeni bir falcı başvurusu alındı.\n\nAd Soyad: %s\nRumuz: %s\nŞehir: %s\n\nYönetim paneli:\n%s",
			$data['ad_soyad'],
			$data['rumuz'],
			$data['sehir'],
			admin_url( "post.php?post={$post_id}&action=edit" )
		);

		wp_mail( $to, $subject, $body );
	}
}
