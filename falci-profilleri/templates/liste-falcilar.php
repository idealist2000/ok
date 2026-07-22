<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Variables: $uzmanlik, $limit, $columns
 */
$paged         = max( 1, get_query_var( 'paged' ) );
$query         = Falci_Post_Type::query_falcilar( $uzmanlik, $limit, $paged );
$tum_uzman     = Falci_Post_Type::get_uzmanlik_alanlari();
$current_uzman = $uzmanlik;
?>

<div class="falci-liste-wrapper">

    <!-- Filter Bar -->
    <div class="falci-filtre-bar">
        <span class="falci-filtre-label">&#10024; Uzmanlığa Göre Filtrele:</span>
        <div class="falci-filtre-buttons">
            <?php
            $base_url = get_permalink();
            $all_active = empty( $current_uzman ) ? ' active' : '';
            echo '<a href="' . esc_url( $base_url ) . '" class="falci-filtre-btn' . $all_active . '" data-uzmanlik="">Tümü</a>';
            foreach ( $tum_uzman as $key => $label ) {
                $active = ( $current_uzman === $key ) ? ' active' : '';
                $url    = add_query_arg( 'uzmanlik', $key, $base_url );
                echo '<a href="' . esc_url( $url ) . '" class="falci-filtre-btn' . $active . '" data-uzmanlik="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</a>';
            }
            ?>
        </div>
    </div>

    <?php if ( $query->have_posts() ) : ?>

    <div class="falci-grid falci-cols-<?php echo esc_attr( $columns ); ?>">
        <?php while ( $query->have_posts() ) :
            $query->the_post();
            $post_id       = get_the_ID();
            $rumuz         = get_post_meta( $post_id, '_falci_rumuz', true ) ?: get_the_title();
            $sehir         = get_post_meta( $post_id, '_falci_sehir', true );
            $deneyim       = (int) get_post_meta( $post_id, '_falci_deneyim_yili', true );
            $seans_ucreti  = get_post_meta( $post_id, '_falci_seans_ucreti', true );
            $tanitim       = get_post_meta( $post_id, '_falci_tanitim', true );
            $uzmanliklar   = (array) get_post_meta( $post_id, '_falci_uzmanliklar', true );
            $foto_id       = (int) get_post_meta( $post_id, '_falci_foto_id', true );
            $profil_url    = get_permalink();

            $wa_msg = urlencode( 'Merhaba, ' . $rumuz . ' hakkında bilgi almak istiyorum.' );
        ?>
        <div class="falci-kart">
            <div class="falci-kart-foto">
                <a href="<?php echo esc_url( $profil_url ); ?>">
                    <?php if ( $foto_id ) : ?>
                        <?php echo wp_get_attachment_image( $foto_id, array( 200, 200 ), false, array( 'class' => 'falci-avatar', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <div class="falci-avatar-placeholder">&#9733;</div>
                    <?php endif; ?>
                </a>
            </div>

            <div class="falci-kart-body">
                <h3 class="falci-kart-isim">
                    <a href="<?php echo esc_url( $profil_url ); ?>"><?php echo esc_html( $rumuz ); ?></a>
                </h3>

                <?php if ( $sehir ) : ?>
                <p class="falci-kart-sehir">&#128205; <?php echo esc_html( $sehir ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $uzmanliklar ) ) : ?>
                <div class="falci-kart-etiketler">
                    <?php foreach ( $uzmanliklar as $u_key ) :
                        if ( isset( $tum_uzman[ $u_key ] ) ) : ?>
                    <span class="falci-etiket"><?php echo esc_html( $tum_uzman[ $u_key ] ); ?></span>
                    <?php endif; endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( $deneyim ) : ?>
                <p class="falci-kart-deneyim">&#9203; <?php echo esc_html( $deneyim ); ?> yıl deneyim</p>
                <?php endif; ?>

                <?php if ( $tanitim ) : ?>
                <p class="falci-kart-tanitim"><?php echo esc_html( wp_trim_words( $tanitim, 15 ) ); ?></p>
                <?php endif; ?>

                <?php if ( $seans_ucreti ) : ?>
                <p class="falci-kart-ucret">&#128176; <?php echo esc_html( $seans_ucreti ); ?></p>
                <?php endif; ?>
            </div>

            <div class="falci-kart-footer">
                <a href="tel:<?php echo esc_attr( FALCI_PHONE ); ?>" class="falci-btn falci-btn-ara">
                    &#128222; Ara
                </a>
                <a href="<?php echo esc_url( FALCI_WHATSAPP_BASE . '?text=' . $wa_msg ); ?>"
                   class="falci-btn falci-btn-wp" target="_blank" rel="noopener noreferrer">
                    &#128279; WhatsApp
                </a>
            </div>
        </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div><!-- .falci-grid -->

    <?php
    // Pagination
    $total_pages = $query->max_num_pages;
    if ( $total_pages > 1 ) :
        echo '<div class="falci-pagination">';
        echo paginate_links( array(
            'total'   => $total_pages,
            'current' => $paged,
            'format'  => '?paged=%#%',
            'prev_text' => '&#8592; Önceki',
            'next_text' => 'Sonraki &#8594;',
        ) );
        echo '</div>';
    endif;
    ?>

    <?php else : ?>
    <div class="falci-bos-sonuc">
        <div class="falci-mystic-icon">&#9733;</div>
        <h3>Henüz bu kategoride falcı bulunmuyor.</h3>
        <p>Yakında eklenecek...</p>
    </div>
    <?php endif; ?>

</div><!-- .falci-liste-wrapper -->
