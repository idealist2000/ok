<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/** @var array $uzmanliklar */
?>
<div class="falci-form-wrapper" id="falci-basvuru-wrapper">

    <div class="falci-form-header">
        <div class="falci-mystic-icon">&#9733;</div>
        <h2>Falcı Başvuru Formu</h2>
        <p>Topluluğumuza katılmak için aşağıdaki formu eksiksiz doldurunuz.</p>
    </div>

    <div id="falci-form-mesaj" class="falci-mesaj" style="display:none;"></div>

    <form id="falci-basvuru-form" enctype="multipart/form-data" novalidate>
        <?php wp_nonce_field( 'falci_form_nonce', 'falci_wp_nonce' ); ?>

        <!-- Honeypot: must be hidden and empty -->
        <div style="position:absolute;left:-9999px;top:-9999px;visibility:hidden;" aria-hidden="true">
            <label for="hp_website">Website</label>
            <input type="text" id="hp_website" name="hp_website" tabindex="-1" autocomplete="off" value="" />
        </div>

        <!-- SECTION 1: Kişisel Bilgiler -->
        <fieldset class="falci-fieldset">
            <legend>&#9320; Kişisel Bilgiler</legend>
            <div class="falci-row">
                <div class="falci-field">
                    <label for="ff_ad_soyad">Ad Soyad <span class="req">*</span></label>
                    <input type="text" id="ff_ad_soyad" name="ad_soyad"
                           placeholder="Gerçek adınız (herkese gösterilmez)" required />
                    <span class="falci-field-note">Profilde yalnızca rumuzunuz görünür.</span>
                </div>
                <div class="falci-field">
                    <label for="ff_rumuz">Rumuz / Mahlas <span class="req">*</span></label>
                    <input type="text" id="ff_rumuz" name="rumuz"
                           placeholder="Örn: Ayşe Hanım, Büyük Üstat…" required />
                </div>
            </div>
            <div class="falci-row">
                <div class="falci-field">
                    <label for="ff_sehir">Şehir <span class="req">*</span></label>
                    <input type="text" id="ff_sehir" name="sehir" placeholder="İstanbul" required />
                </div>
                <div class="falci-field">
                    <label for="ff_deneyim_yili">Deneyim Yılı <span class="req">*</span></label>
                    <input type="number" id="ff_deneyim_yili" name="deneyim_yili"
                           min="0" max="80" placeholder="5" required />
                </div>
            </div>
        </fieldset>

        <!-- SECTION 2: Profil Fotoğrafı -->
        <fieldset class="falci-fieldset">
            <legend>&#128247; Profil Fotoğrafı</legend>
            <div class="falci-field">
                <label for="ff_profil_foto">Fotoğraf Yükle (JPG/PNG/WebP, maks. 5 MB)</label>
                <input type="file" id="ff_profil_foto" name="profil_foto"
                       accept="image/jpeg,image/png,image/gif,image/webp" />
                <div id="ff-foto-preview" style="display:none;margin-top:10px;">
                    <img id="ff-foto-preview-img" src="" alt="Önizleme"
                         style="width:100px;height:100px;object-fit:cover;border-radius:50%;border:3px solid var(--falci-purple);" />
                </div>
            </div>
        </fieldset>

        <!-- SECTION 3: Uzmanlık Alanları -->
        <fieldset class="falci-fieldset">
            <legend>&#10024; Uzmanlık Alanları <span class="req">*</span></legend>
            <p class="falci-field-note">En az bir alan seçiniz. Birden fazla seçebilirsiniz.</p>
            <div class="falci-uzmanlik-grid">
                <?php foreach ( $uzmanliklar as $key => $label ) : ?>
                <label class="falci-uzmanlik-label">
                    <input type="checkbox" name="uzmanliklar[]" value="<?php echo esc_attr( $key ); ?>" />
                    <span><?php echo esc_html( $label ); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <!-- SECTION 4: Çalışma Bilgileri -->
        <fieldset class="falci-fieldset">
            <legend>&#128197; Çalışma Bilgileri</legend>
            <div class="falci-row">
                <div class="falci-field">
                    <label for="ff_calisma_saatleri">Çalışma Saatleri <span class="req">*</span></label>
                    <input type="text" id="ff_calisma_saatleri" name="calisma_saatleri"
                           placeholder="Örn: Hafta içi 10:00–20:00" required />
                </div>
                <div class="falci-field">
                    <label for="ff_seans_ucreti">Seans Ücreti <span class="req">*</span></label>
                    <input type="text" id="ff_seans_ucreti" name="seans_ucreti"
                           placeholder="Örn: 150 TL / seans" required />
                </div>
            </div>
        </fieldset>

        <!-- SECTION 5: Tanıtım -->
        <fieldset class="falci-fieldset">
            <legend>&#128221; Tanıtım ve Biyografi</legend>
            <div class="falci-field">
                <label for="ff_tanitim">Kısa Tanıtım Metni</label>
                <textarea id="ff_tanitim" name="tanitim" rows="3"
                          placeholder="Kendinizi kısaca tanıtın (50-150 karakter)…"></textarea>
                <span class="falci-field-note">Listelemelerde görünecek özet metin.</span>
            </div>
            <div class="falci-field">
                <label for="ff_biyografi">Detaylı Biyografi <span class="req">*</span></label>
                <textarea id="ff_biyografi" name="biyografi" rows="8"
                          placeholder="Kendinizi, uzmanlıklarınızı ve deneyimlerinizi ayrıntılı anlatın… (en az 300 karakter)"
                          required></textarea>
                <div class="falci-char-counter">
                    <span id="ff-biyo-count">0</span> / 300 karakter
                    (minimum 300 karakter gerekli)
                </div>
                <span class="falci-field-note">
                    &#9888; Telefon numarası, web sitesi linki veya sosyal medya hesabı eklemeyin.
                    Tüm iletişim platformumuz üzerinden yapılmaktadır.
                </span>
            </div>
        </fieldset>

        <!-- SECTION 6: İletişim Uyarısı -->
        <div class="falci-iletisim-uyari">
            <p>
                &#128222; Tüm müşteri iletişimi platformumuz üzerinden yönetilir.
                Profil sayfanızda kendi iletişim bilgilerinizi paylaşamazsınız.
                İletişim bilgisi içeren başvurular otomatik reddedilir.
            </p>
        </div>

        <div class="falci-submit-area">
            <button type="submit" id="falci-submit-btn" class="falci-btn falci-btn-primary">
                <span class="btn-text">Başvuruyu Gönder</span>
                <span class="btn-spinner" style="display:none;">&#9733; Gönderiliyor…</span>
            </button>
        </div>

    </form><!-- #falci-basvuru-form -->

</div><!-- .falci-form-wrapper -->
