<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $settings */
?>
<div class="wrap">
    <h1>Falcı Profilleri — Ayarlar</h1>

    <?php settings_errors( 'falci_settings_group' ); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'falci_settings_group' ); ?>

        <table class="form-table" role="presentation">

            <tr>
                <th scope="row">
                    <label for="ai_provider">YZ Sağlayıcısı</label>
                </th>
                <td>
                    <select name="falci_settings[ai_provider]" id="ai_provider">
                        <option value="openai" <?php selected( $settings['ai_provider'] ?? 'openai', 'openai' ); ?>>
                            OpenAI (GPT-3.5)
                        </option>
                        <option value="claude" <?php selected( $settings['ai_provider'] ?? '', 'claude' ); ?>>
                            Anthropic (Claude Haiku)
                        </option>
                    </select>
                    <p class="description">Başvuruları denetleyecek yapay zeka modeli.</p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="api_key">API Anahtarı</label>
                </th>
                <td>
                    <input type="password" id="api_key" name="falci_settings[api_key]"
                           value="<?php echo esc_attr( $settings['api_key'] ?? '' ); ?>"
                           class="regular-text" autocomplete="off" />
                    <p class="description">
                        OpenAI için <code>sk-…</code> ile başlar. Claude için <code>sk-ant-…</code> ile başlar.
                        Boş bırakırsanız otomatik YZ denetimi devre dışı kalır.
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">Otomatik YZ Onayı</th>
                <td>
                    <label>
                        <input type="checkbox" name="falci_settings[auto_approve]" value="1"
                               <?php checked( $settings['auto_approve'] ?? '1', '1' ); ?> />
                        Başvurular YZ tarafından otomatik olarak onaylansın / reddedilsin
                    </label>
                    <p class="description">
                        Kapalıysa tüm başvurular "Beklemede" durumunda kalır; admin manuel onay verir.
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bildirim_email">Bildirim E-postası</label>
                </th>
                <td>
                    <input type="email" id="bildirim_email" name="falci_settings[bildirim_email]"
                           value="<?php echo esc_attr( $settings['bildirim_email'] ?? get_option( 'admin_email' ) ); ?>"
                           class="regular-text" />
                    <p class="description">Yeni başvurularda bu adrese bildirim gönderilir.</p>
                </td>
            </tr>

        </table>

        <?php submit_button( 'Ayarları Kaydet' ); ?>
    </form>

    <hr>

    <h2>Shortcode Referansı</h2>
    <table class="widefat striped" style="max-width:700px;">
        <thead>
            <tr><th>Shortcode</th><th>Açıklama</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><code>[falci_basvuru_formu]</code></td>
                <td>Falcı başvuru formunu sayfaya ekler.</td>
            </tr>
            <tr>
                <td><code>[falci_listesi]</code></td>
                <td>Tüm onaylı falcıları listeler. <code>uzmanlik="tarot"</code>, <code>limit="12"</code>, <code>columns="3"</code> parametreleri desteklenir.</td>
            </tr>
            <tr>
                <td><code>[falci_profil id="123"]</code></td>
                <td>Belirli bir falcının profilini sayfaya gömer.</td>
            </tr>
        </tbody>
    </table>

    <h2 style="margin-top:20px;">İletişim Numarası</h2>
    <p>Tüm profil sayfalarındaki iletişim butonları sabit olarak şu numaraya yönlendirilmektedir:</p>
    <p><strong style="font-size:18px;"><?php echo esc_html( FALCI_PHONE_DISPLAY ); ?></strong></p>
    <p>Bu numara <code>falci-profilleri.php</code> dosyasındaki <code>FALCI_PHONE</code> sabiti ile yönetilir.</p>
</div>
