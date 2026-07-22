<?php
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$uzmanlik = isset( $_GET['uzmanlik'] ) ? sanitize_key( $_GET['uzmanlik'] ) : '';
$limit    = 12;
$columns  = 3;
?>

<div class="falci-archive-hero">
    <div class="falci-archive-hero-inner">
        <div class="falci-mystic-icon">&#9733;</div>
        <h1>Falcı Rehberi</h1>
        <p>Profesyonel ve deneyimli falcılarımızla tanışın</p>
    </div>
</div>

<?php
// Render the same listing template used by shortcode
include FALCI_PLUGIN_DIR . 'templates/liste-falcilar.php';

get_footer();
