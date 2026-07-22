<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Embedded single profile for [falci_profil id="X"] shortcode.
 * Variable: $post_id
 */
$durum = get_post_meta( $post_id, '_falci_durum', true );
if ( 'yayinda' !== $durum ) {
    echo '<p class="falci-mesaj falci-mesaj-hata">Bu profil şu an görüntülenemiyor.</p>';
    return;
}

$rumuz       = get_post_meta( $post_id, '_falci_rumuz', true ) ?: get_the_title( $post_id );
$sehir       = get_post_meta( $post_id, '_falci_sehir', true );
$deneyim     = (int) get_post_meta( $post_id, '_falci_deneyim_yili', true );
$biyografi   = get_post_meta( $post_id, '_falci_biyografi', true );
$tanitim     = get_post_meta( $post_id, '_falci_tanitim', true );
$calisma     = get_post_meta( $post_id, '_falci_calisma_saatleri', true );
$ucret       = get_post_meta( $post_id, '_falci_seans_ucreti', true );
$uzmanliklar = (array) get_post_meta( $post_id, '_falci_uzmanliklar', true );
$foto_id     = (int) get_post_meta( $post_id, '_falci_foto_id', true );
$tum_uzman   = Falci_Post_Type::get_uzmanlik_alanlari();
$profil_url  = get_permalink( $post_id );
$wa_msg      = urlencode( 'Merhaba, ' . $rumuz . ' hakkında bilgi almak istiyorum.' );
?>
<div class="falci-profil-wrapper falci-profil-embed">
    <div class="falci-profil-hero">
        <div class="falci-profil-foto">
            <?php if ( $foto_id ) : ?>
                <?php echo wp_get_attachment_image( $foto_id, array( 180, 180 ), false, array( 'class' => 'falci-profil-avatar' ) ); ?>
            <?php else : ?>
                <div class="falci-profil-avatar-placeholder">&#9733;</div>
            <?php endif; ?>
        </div>
        <div class="falci-profil-hero-info">
            <h2 class="falci-profil-isim">
                <a href="<?php echo esc_url( $profil_url ); ?>"><?php echo esc_html( $rumuz ); ?></a>
            </h2>
            <?php if ( $tanitim ) : ?>
            <p class="falci-profil-tanitim"><?php echo esc_html( $tanitim ); ?></p>
            <?php endif; ?>
            <div class="falci-profil-meta-satir">
                <?php if ( $sehir ) echo '<span>&#128205; ' . esc_html( $sehir ) . '</span>'; ?>
                <?php if ( $deneyim ) echo '<span>&#9203; ' . esc_html( $deneyim ) . ' yıl deneyim</span>'; ?>
                <?php if ( $ucret ) echo '<span>&#128176; ' . esc_html( $ucret ) . '</span>'; ?>
            </div>
            <?php if ( ! empty( $uzmanliklar ) ) : ?>
            <div class="falci-profil-etiketler">
                <?php foreach ( $uzmanliklar as $u ) :
                    if ( isset( $tum_uzman[ $u ] ) ) : ?>
                <span class="falci-etiket"><?php echo esc_html( $tum_uzman[ $u ] ); ?></span>
                <?php endif; endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="falci-profil-iletisim">
                <a href="tel:<?php echo esc_attr( FALCI_PHONE ); ?>" class="falci-btn falci-btn-ara">
                    &#128222; Ara
                </a>
                <a href="<?php echo esc_url( FALCI_WHATSAPP_BASE . '?text=' . $wa_msg ); ?>"
                   class="falci-btn falci-btn-wp" target="_blank" rel="noopener noreferrer">
                    &#128279; WhatsApp
                </a>
            </div>
        </div>
    </div>
    <?php if ( $biyografi ) : ?>
    <div class="falci-profil-bolum">
        <p><?php echo wp_kses_post( nl2br( esc_html( $biyografi ) ) ); ?></p>
    </div>
    <?php endif; ?>
</div>
