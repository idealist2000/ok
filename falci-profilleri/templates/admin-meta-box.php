<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Variables available: $post, $meta, $uzmanliklar_list, $selected
 */
?>
<style>
.falci-meta-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.falci-meta-grid .full { grid-column:1/-1; }
.falci-meta-grid label { display:block; font-weight:600; margin-bottom:4px; }
.falci-meta-grid input[type=text],
.falci-meta-grid input[type=number],
.falci-meta-grid textarea,
.falci-meta-grid select { width:100%; }
.falci-meta-grid textarea { min-height:100px; }
#_falci_biyografi { min-height:160px; }
.falci-uzmanlik-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:8px; margin-top:6px; }
.falci-uzmanlik-grid label { font-weight:normal; display:flex; align-items:center; gap:6px; }
.falci-foto-preview { display:flex; align-items:center; gap:12px; margin-top:8px; }
.falci-foto-preview img { width:80px; height:80px; object-fit:cover; border-radius:50%; border:2px solid #ddd; }
</style>

<div class="falci-meta-grid">

    <div>
        <label for="_falci_ad_soyad">Ad Soyad <span style="color:red">*</span></label>
        <input type="text" id="_falci_ad_soyad" name="_falci_ad_soyad"
               value="<?php echo esc_attr( $meta['_falci_ad_soyad'] ); ?>" />
        <p class="description">Gerçek ad/soyad — herkese açık gösterilmez.</p>
    </div>

    <div>
        <label for="_falci_rumuz">Rumuz / Mahlas <span style="color:red">*</span></label>
        <input type="text" id="_falci_rumuz" name="_falci_rumuz"
               value="<?php echo esc_attr( $meta['_falci_rumuz'] ); ?>" />
    </div>

    <div>
        <label for="_falci_sehir">Şehir</label>
        <input type="text" id="_falci_sehir" name="_falci_sehir"
               value="<?php echo esc_attr( $meta['_falci_sehir'] ); ?>" />
    </div>

    <div>
        <label for="_falci_deneyim_yili">Deneyim (Yıl)</label>
        <input type="number" id="_falci_deneyim_yili" name="_falci_deneyim_yili" min="0" max="80"
               value="<?php echo esc_attr( $meta['_falci_deneyim_yili'] ); ?>" />
    </div>

    <div>
        <label for="_falci_seans_ucreti">Seans Ücreti</label>
        <input type="text" id="_falci_seans_ucreti" name="_falci_seans_ucreti"
               placeholder="Örn: 200 TL / saat"
               value="<?php echo esc_attr( $meta['_falci_seans_ucreti'] ); ?>" />
    </div>

    <div>
        <label for="_falci_calisma_saatleri">Çalışma Saatleri</label>
        <input type="text" id="_falci_calisma_saatleri" name="_falci_calisma_saatleri"
               placeholder="Örn: Hafta içi 10:00-18:00"
               value="<?php echo esc_attr( $meta['_falci_calisma_saatleri'] ); ?>" />
    </div>

    <div class="full">
        <label>Uzmanlık Alanları</label>
        <div class="falci-uzmanlik-grid">
            <?php foreach ( $uzmanliklar_list as $key => $label ) : ?>
            <label>
                <input type="checkbox" name="_falci_uzmanliklar[]"
                       value="<?php echo esc_attr( $key ); ?>"
                       <?php checked( in_array( $key, $selected, true ) ); ?> />
                <?php echo esc_html( $label ); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="full">
        <label for="_falci_tanitim">Hakkında / Tanıtım Metni</label>
        <textarea id="_falci_tanitim" name="_falci_tanitim"><?php echo esc_textarea( $meta['_falci_tanitim'] ); ?></textarea>
    </div>

    <div class="full">
        <label for="_falci_biyografi">Detaylı Biyografi (en az 300 karakter)</label>
        <textarea id="_falci_biyografi" name="_falci_biyografi"><?php echo esc_textarea( $meta['_falci_biyografi'] ); ?></textarea>
        <p class="description">Telefon numarası, link veya iletişim bilgisi içermemeli.</p>
    </div>

    <div class="full">
        <label>Profil Fotoğrafı</label>
        <div class="falci-foto-preview">
            <?php
            $foto_id = (int) $meta['_falci_foto_id'];
            if ( $foto_id ) {
                echo wp_get_attachment_image( $foto_id, array( 80, 80 ), false, array( 'id' => 'falci-foto-preview-img', 'style' => 'border-radius:50%;width:80px;height:80px;object-fit:cover;' ) );
            } else {
                echo '<img id="falci-foto-preview-img" src="" style="display:none;border-radius:50%;width:80px;height:80px;object-fit:cover;" />';
            }
            ?>
            <div>
                <input type="hidden" id="_falci_foto_id" name="_falci_foto_id"
                       value="<?php echo esc_attr( $foto_id ); ?>" />
                <button type="button" class="button" id="falci-foto-secici">
                    <?php echo $foto_id ? 'Fotoğrafı Değiştir' : 'Fotoğraf Seç'; ?>
                </button>
                <?php if ( $foto_id ) : ?>
                <button type="button" class="button" id="falci-foto-kaldir" style="margin-left:6px;">Fotoğrafı Kaldır</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
