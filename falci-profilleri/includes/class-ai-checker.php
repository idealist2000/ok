<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Falci_AI_Checker {

	private $settings;

	public function __construct() {
		$this->settings = get_option( 'falci_settings', array() );
	}

	/**
	 * Checks application content with the configured AI provider.
	 *
	 * @param  array $data  Sanitized form data.
	 * @return array { onaylandi: bool, mesaj: string }
	 */
	public function check_content( $data ) {
		$api_key  = $this->settings['api_key'] ?? '';
		$provider = $this->settings['ai_provider'] ?? 'openai';

		if ( empty( $api_key ) ) {
			return array(
				'onaylandi' => true,
				'mesaj'     => 'API anahtarı tanımlı değil; manuel inceleme gerekebilir.',
			);
		}

		$prompt = $this->build_prompt( $data );

		if ( 'claude' === $provider ) {
			return $this->call_claude( $prompt, $api_key );
		}

		return $this->call_openai( $prompt, $api_key );
	}

	// ------------------------------------------------------------------ Prompt

	private function build_prompt( $data ) {
		$tum_uzman   = Falci_Post_Type::get_uzmanlik_alanlari();
		$uzman_names = array_map(
			function( $k ) use ( $tum_uzman ) {
				return isset( $tum_uzman[ $k ] ) ? $tum_uzman[ $k ] : $k;
			},
			(array) $data['uzmanliklar']
		);

		return
			"Aşağıdaki Türkçe falcı profil başvurusunu modere et.\n\n" .
			"Reddetme kriterleri (herhangi biri geçerliyse REDDET):\n" .
			"- Küfür, hakaret veya uygunsuz içerik\n" .
			"- Biyografide telefon numarası, URL, e-posta, sosyal medya hesabı\n" .
			"- Anlamsız veya spam içerik (rastgele karakterler, tekrar eden sözcükler)\n" .
			"- Açıkça gerçekçi olmayan iddialar (örn. 'Ölülerle konuşabilirim, garantili')\n" .
			"- Eksik veya tutarsız bilgi\n\n" .
			"Başvuru:\n" .
			"Rumuz: " . $data['rumuz'] . "\n" .
			"Uzmanlık: " . implode( ', ', $uzman_names ) . "\n" .
			"Deneyim: " . $data['deneyim_yili'] . " yıl\n" .
			"Şehir: " . $data['sehir'] . "\n" .
			"Seans Ücreti: " . $data['seans_ucreti'] . "\n" .
			"Çalışma Saatleri: " . $data['calisma_saatleri'] . "\n" .
			"Biyografi: " . $data['biyografi'] . "\n" .
			"Tanıtım: " . $data['tanitim'] . "\n\n" .
			"Yanıtını SADECE şu iki satır formatında ver:\n" .
			"KARAR: ONAYLA\n" .
			"GEREKÇE: [tek satır açıklama]\n\n" .
			"veya\n\n" .
			"KARAR: REDDET\n" .
			"GEREKÇE: [tek satır açıklama]\n";
	}

	// ------------------------------------------------------------------ OpenAI

	private function call_openai( $prompt, $api_key ) {
		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'model'       => 'gpt-3.5-turbo',
					'temperature' => 0.1,
					'max_tokens'  => 150,
					'messages'    => array(
						array(
							'role'    => 'system',
							'content' => 'Sen bir içerik moderatörüsün. Yalnızca istenen formatta yanıt ver.',
						),
						array( 'role' => 'user', 'content' => $prompt ),
					),
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->api_error( $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['choices'][0]['message']['content'] ?? '';

		return $text ? $this->parse_decision( $text ) : $this->api_error( 'Boş yanıt.' );
	}

	// ------------------------------------------------------------------ Claude

	private function call_claude( $prompt, $api_key ) {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 30,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'model'      => 'claude-haiku-4-5-20251001',
					'max_tokens' => 150,
					'system'     => 'Sen bir içerik moderatörüsün. Yalnızca istenen formatta yanıt ver.',
					'messages'   => array(
						array( 'role' => 'user', 'content' => $prompt ),
					),
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->api_error( $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['content'][0]['text'] ?? '';

		return $text ? $this->parse_decision( $text ) : $this->api_error( 'Boş yanıt.' );
	}

	// ------------------------------------------------------------------ Parser

	private function parse_decision( $content ) {
		$onaylandi = false;
		$mesaj     = trim( $content );

		if ( preg_match( '/KARAR\s*:\s*(ONAYLA|REDDET)/ui', $content, $m ) ) {
			$onaylandi = ( mb_strtoupper( $m[1] ) === 'ONAYLA' );
		}

		if ( preg_match( '/GEREKÇE\s*:\s*(.+)/ui', $content, $m ) ) {
			$mesaj = trim( $m[1] );
		}

		return array( 'onaylandi' => $onaylandi, 'mesaj' => $mesaj );
	}

	private function api_error( $detail ) {
		return array(
			'onaylandi' => false,
			'mesaj'     => 'AI API hatası: ' . $detail . ' Manuel inceleme gerekiyor.',
		);
	}
}
