<?php 
// Função para registrar o custom post type
function register_imoveis_post_type() {
    $labels = array(
        'name' => 'Imóveis',
        'singular_name' => 'Imóvel',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    );

    register_post_type('imovel', $args);
}
add_action('init', 'register_imoveis_post_type');
?>
