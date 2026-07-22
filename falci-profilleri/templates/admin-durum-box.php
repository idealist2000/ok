<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Variables: $post, $durum, $ret_gerekce, $ai_sonuc
 */
$durum_map = array(
    'yayinda'    => array( 'label' => 'Yayında',    'color' => '#28a745' ),
    'beklemede'  => array( 'label' => 'Beklemede',  'color' => '#ffc107' ),
    'reddedildi' => array( 'label' => 'Reddedildi', 'color' => '#dc3545' ),
);
$current = $durum_map[ $durum ] ?? array( 'label' => $durum, 'color' => '#6c757d' );
?>
<p>
    <strong>Mevcut Durum:</strong><br>
    <span style="display:inline-block;margin-top:4px;padding:4px 10px;background:<?php echo esc_attr( $current['color'] ); ?>;color:#fff;border-radius:4px;font-size:13px;">
        <?php echo esc_html( $current['label'] ); ?>
    </span>
</p>

<p>
    <label for="_falci_durum"><strong>Durumu Değiştir:</strong></label><br>
    <select name="_falci_durum" id="_falci_durum" style="width:100%;margin-top:4px;">
        <option value="beklemede"  <?php selected( $durum, 'beklemede' ); ?>>Beklemede</option>
        <option value="yayinda"    <?php selected( $durum, 'yayinda' ); ?>>Yayında</option>
        <option value="reddedildi" <?php selected( $durum, 'reddedildi' ); ?>>Reddedildi</option>
    </select>
</p>

<?php if ( $ret_gerekce ) : ?>
<div style="background:#fff3cd;border:1px solid #ffc107;padding:8px;border-radius:4px;margin-top:8px;">
    <p style="margin:0;"><strong>Red Gerekçesi:</strong></p>
    <p style="margin:4px 0 0;"><?php echo esc_html( $ret_gerekce ); ?></p>
</div>
<p>
    <label for="_falci_ret_gerekce"><strong>Gerekçeyi Güncelle:</strong></label><br>
    <textarea name="_falci_ret_gerekce" id="_falci_ret_gerekce" rows="3"
              style="width:100%;margin-top:4px;"><?php echo esc_textarea( $ret_gerekce ); ?></textarea>
</p>
<?php else : ?>
<p>
    <label for="_falci_ret_gerekce"><strong>Red Gerekçesi (opsiyonel):</strong></label><br>
    <textarea name="_falci_ret_gerekce" id="_falci_ret_gerekce" rows="3"
              style="width:100%;margin-top:4px;"></textarea>
</p>
<?php endif; ?>

<?php if ( $ai_sonuc ) : ?>
<div style="background:#f0f0f0;border:1px solid #ccc;padding:8px;border-radius:4px;margin-top:8px;font-size:12px;">
    <strong>YZ Kontrol Sonucu:</strong><br>
    <?php echo esc_html( $ai_sonuc ); ?>
</div>
<?php endif; ?>

<hr style="margin:12px 0;">
<p style="font-size:12px;color:#666;margin:0;">
    Profili kaydetmek için sayfanın sağ üstündeki <strong>Güncelle</strong> butonunu kullanın.
</p>
