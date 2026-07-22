<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
    the_post();
    $post_id      = get_the_ID();
    $durum        = get_post_meta( $post_id, '_falci_durum', true );

    // Only show published profiles to non-admins
    if ( 'yayinda' !== $durum && ! current_user_can( 'edit_posts' ) ) {
        echo '<div class="falci-profil-wrapper"><p>Bu profil şu an görüntülenemiyor.</p></div>';
        get_footer();
        return;
    }

    $rumuz         = get_post_meta( $post_id, '_falci_rumuz', true ) ?: get_the_title();
    $sehir         = get_post_meta( $post_id, '_falci_sehir', true );
    $deneyim       = (int) get_post_meta( $post_id, '_falci_deneyim_yili', true );
    $biyografi     = get_post_meta( $post_id, '_falci_biyografi', true );
    $tanitim       = get_post_meta( $post_id, '_falci_tanitim', true );
    $calisma       = get_post_meta( $post_id, '_falci_calisma_saatleri', true );
    $ucret         = get_post_meta( $post_id, '_falci_seans_ucreti', true );
    $uzmanliklar   = (array) get_post_meta( $post_id, '_falci_uzmanliklar', true );
    $foto_id       = (int) get_post_meta( $post_id, '_falci_foto_id', true );
    $tum_uzman     = Falci_Post_Type::get_uzmanlik_alanlari();

    $wa_msg = urlencode( 'Merhaba, ' . $rumuz . ' hakkında bilgi almak istiyorum.' );
?>
<div class="falci-profil-wrapper">

    <!-- Hero -->
    <div class="falci-profil-hero">
        <div class="falci-profil-foto">
            <?php if ( $foto_id ) : ?>
                <?php echo wp_get_attachment_image( $foto_id, array( 250, 250 ), false, array( 'class' => 'falci-profil-avatar' ) ); ?>
            <?php else : ?>
                <div class="falci-profil-avatar-placeholder">&#9733;</div>
            <?php endif; ?>
        </div>

        <div class="falci-profil-hero-info">
            <h1 class="falci-profil-isim"><?php echo esc_html( $rumuz ); ?></h1>

            <?php if ( $tanitim ) : ?>
            <p class="falci-profil-tanitim"><?php echo esc_html( $tanitim ); ?></p>
            <?php endif; ?>

            <div class="falci-profil-meta-satir">
                <?php if ( $sehir ) : ?>
                <span>&#128205; <?php echo esc_html( $sehir ); ?></span>
                <?php endif; ?>
                <?php if ( $deneyim ) : ?>
                <span>&#9203; <?php echo esc_html( $deneyim ); ?> yıl deneyim</span>
                <?php endif; ?>
                <?php if ( $ucret ) : ?>
                <span>&#128176; <?php echo esc_html( $ucret ); ?></span>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $uzmanliklar ) ) : ?>
            <div class="falci-profil-etiketler">
                <?php foreach ( $uzmanliklar as $u_key ) :
                    if ( isset( $tum_uzman[ $u_key ] ) ) : ?>
                <span class="falci-etiket"><?php echo esc_html( $tum_uzman[ $u_key ] ); ?></span>
                <?php endif; endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Contact Buttons -->
            <div class="falci-profil-iletisim">
                <a href="tel:<?php echo esc_attr( FALCI_PHONE ); ?>"
                   class="falci-btn falci-btn-ara falci-btn-buyuk">
                    &#128222; <?php echo esc_html( FALCI_PHONE_DISPLAY ); ?> — Ara
                </a>
                <a href="<?php echo esc_url( FALCI_WHATSAPP_BASE . '?text=' . $wa_msg ); ?>"
                   class="falci-btn falci-btn-wp falci-btn-buyuk"
                   target="_blank" rel="noopener noreferrer">
                    &#128279; WhatsApp'tan Yaz
                </a>
            </div>
        </div>
    </div><!-- .falci-profil-hero -->

    <!-- Detail Sections -->
    <div class="falci-profil-detay">

        <?php if ( $biyografi ) : ?>
        <div class="falci-profil-bolum">
            <h2>Hakkımda</h2>
            <div class="falci-profil-biyografi">
                <?php echo wp_kses_post( nl2br( $biyografi ) ); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="falci-profil-bolum falci-profil-info-grid">

            <?php if ( ! empty( $uzmanliklar ) ) : ?>
            <div class="falci-profil-info-kart">
                <h3>&#10024; Uzmanlık Alanları</h3>
                <ul>
                    <?php foreach ( $uzmanliklar as $u_key ) :
                        if ( isset( $tum_uzman[ $u_key ] ) ) : ?>
                    <li><?php echo esc_html( $tum_uzman[ $u_key ] ); ?></li>
                    <?php endif; endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="falci-profil-info-kart">
                <h3>&#128197; Çalışma Bilgileri</h3>
                <ul>
                    <?php if ( $calisma ) : ?>
                    <li><strong>Saatler:</strong> <?php echo esc_html( $calisma ); ?></li>
                    <?php endif; ?>
                    <?php if ( $ucret ) : ?>
                    <li><strong>Ücret:</strong> <?php echo esc_html( $ucret ); ?></li>
                    <?php endif; ?>
                    <?php if ( $sehir ) : ?>
                    <li><strong>Şehir:</strong> <?php echo esc_html( $sehir ); ?></li>
                    <?php endif; ?>
                    <?php if ( $deneyim ) : ?>
                    <li><strong>Deneyim:</strong> <?php echo esc_html( $deneyim ); ?> yıl</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="falci-profil-info-kart falci-iletisim-kart">
                <h3>&#128222; İletişim</h3>
                <p>Randevu ve bilgi için arayın ya da WhatsApp'tan yazın.</p>
                <a href="tel:<?php echo esc_attr( FALCI_PHONE ); ?>" class="falci-btn falci-btn-ara">
                    &#128222; Ara: <?php echo esc_html( FALCI_PHONE_DISPLAY ); ?>
                </a>
                <a href="<?php echo esc_url( FALCI_WHATSAPP_BASE . '?text=' . $wa_msg ); ?>"
                   class="falci-btn falci-btn-wp" target="_blank" rel="noopener noreferrer">
                    &#128279; WhatsApp
                </a>
            </div>

        </div><!-- .falci-profil-info-grid -->

    </div><!-- .falci-profil-detay -->

    <!-- Sticky contact bar (mobile) -->
    <div class="falci-sticky-bar">
        <a href="tel:<?php echo esc_attr( FALCI_PHONE ); ?>" class="falci-btn falci-btn-ara">
            &#128222; Ara
        </a>
        <a href="<?php echo esc_url( FALCI_WHATSAPP_BASE . '?text=' . $wa_msg ); ?>"
           class="falci-btn falci-btn-wp" target="_blank" rel="noopener noreferrer">
            &#128279; WhatsApp
        </a>
    </div>

</div><!-- .falci-profil-wrapper -->
<?php endwhile; ?>

<?php get_footer(); ?>
