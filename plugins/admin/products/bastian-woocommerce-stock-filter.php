<?php
/*
Plugin Name: Bastian Woocommerce Filtrado por cantidad de Stock
Description: Permite filtrar productos en el área de administración por cantidad de stock específica.
Version: 1.1
Author: Bastian
*/

// 1. Agregar el filtro en la interfaz
function custom_admin_products_filter_restrict_manage_posts() {
    global $typenow;

    if ('product' === $typenow) {
        $selected_value = isset($_GET['stock_quantity']) ? intval($_GET['stock_quantity']) : '';
        ?>
        <select name="stock_quantity">
            <option value="">Filtrar por Stock</option>
            <?php
            for ($i = 1; $i <= 10; $i++) {
                echo "<option value='{$i}'" . selected($selected_value, $i, false) . ">{$i}</option>";
            }
            ?>
            <option value="11+" <?php selected($selected_value, '11+'); ?>>11 o más</option>
        </select>
        <?php
    }
}
add_action('restrict_manage_posts', 'custom_admin_products_filter_restrict_manage_posts');

// 2. Manejar el filtrado
function custom_admin_products_filter_query($query) {
    global $pagenow, $post_type;

    if ('edit.php' === $pagenow && 'product' === $post_type && isset($_GET['stock_quantity']) && $_GET['stock_quantity'] !== '') {
        $stock_quantity = $_GET['stock_quantity'];
        
        if ($stock_quantity === '11+') {
            $meta_query = array(
                array(
                    'key' => '_stock',
                    'value' => 11,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                )
            );
        } else {
            $meta_query = array(
                array(
                    'key' => '_stock',
                    'value' => intval($stock_quantity),
                    'compare' => '=',
                    'type' => 'NUMERIC'
                )
            );
        }
        $query->set('meta_query', $meta_query);
    }
}
add_action('pre_get_posts', 'custom_admin_products_filter_query');
?>