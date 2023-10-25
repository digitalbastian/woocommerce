<?php
/*
Plugin Name: Bastian Woocommerce Filtrado por cantidad de Stock
Description: Permite filtrar productos en el área de administración por cantidad de stock específica.
Version: 1.0
Author: Bastian
*/

// 1. Agregar el filtro en la interfaz
function custom_admin_products_filter_restrict_manage_posts() {
    global $typenow;

    if ('product' === $typenow) {
        ?>
        <input type="number" name="stock_quantity" placeholder="Cantidad de Stock" value="<?php echo isset($_GET['stock_quantity']) ? intval($_GET['stock_quantity']) : ''; ?>">
        <?php
    }
}
add_action('restrict_manage_posts', 'custom_admin_products_filter_restrict_manage_posts');

// 2. Manejar el filtrado
function custom_admin_products_filter_query($query) {
    global $pagenow, $post_type;

    if ('edit.php' === $pagenow && 'product' === $post_type && isset($_GET['stock_quantity'])) {
        $stock_quantity = intval($_GET['stock_quantity']);
        $meta_query = array(
            array(
                'key' => '_stock',
                'value' => $stock_quantity,
                'compare' => '=',
                'type' => 'NUMERIC'
            )
        );
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'custom_admin_products_filter_query');
?>
