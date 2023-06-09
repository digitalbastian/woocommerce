<?php
/*
Plugin Name: Cupon de producto gratis
Description: Añade la funcionalidad de agregar productos gratis al carrito
Version: 1.0
Author: Digital Bastian
*/

add_action( 'woocommerce_coupon_options_usage_restriction', 'add_min_quantity_field', 10, 2 );
function add_min_quantity_field( $coupon_get_metabox, $coupon_post ) {
    global $post;
    $discount_type = get_post_meta( $post->ID, 'discount_type', true );
    if( in_array( $discount_type, array( 'fixed_cart' ) ) ) {
        woocommerce_wp_text_input( array(
            'id' => 'min_product_qty',
            'label' => __( 'Cantidad mínima de productos', 'woocommerce' ),
            'placeholder' => __( 'N/A', 'woocommerce' ),
            'description' => __( 'La cantidad mínima de productos en el carrito para que se pueda aplicar el cupón.', 'woocommerce' ),
            'desc_tip' => 'true',
            'type' => 'number',
        ) );
    }
}

// Save the coupon data
add_action( 'woocommerce_coupon_options_save', 'save_min_quantity_field', 10, 2 );
function save_min_quantity_field( $post_id, $coupon ) {
    update_post_meta( $post_id, 'min_product_qty', $_POST['min_product_qty'] );
}

// Validate the coupon
add_filter( 'woocommerce_coupon_is_valid', 'validate_min_product_quantity', 10, 3 );
function validate_min_product_quantity( $is_valid, $coupon, $discounts ) {
    if ( ! $is_valid ) {
        return false; // If the coupon is not valid for another reason, return false
    }

    $discount_type = $coupon->get_discount_type();
    if ( in_array( $discount_type, array( 'fixed_cart' ) ) ) {
        $min_product_qty = get_post_meta( $coupon->get_id(), 'min_product_qty', true );
        if ( ! empty( $min_product_qty ) ) {
            $total_qty = WC()->cart->get_cart_contents_count();
            if ( $total_qty < $min_product_qty ) {
                throw new Exception( sprintf( __( 'Se necesita una cantidad mínima de %s productos para aplicar este cupón!', 'woocommerce' ), $min_product_qty ) );
            }
        }
    }

    return true; // If there's no minimum quantity set or if the cart meets the quantity, return true
}

// Función para añadir el campo personalizado
function wc_cpg_add_product_field() {
    woocommerce_wp_text_input( array(
        'id' => 'wc_cpg_free_products',
        'label' => __( 'Productos Gratis', 'woocommerce' ),
        'description' => __( 'Ingresa los IDs de los productos a asignar gratuitamente, separados por comas.', 'woocommerce' ),
        'desc_tip' => true,
    ) );
}
add_action( 'woocommerce_coupon_options', 'wc_cpg_add_product_field' );

// Función para guardar el valor del campo personalizado
function wc_cpg_save_product_field( $post_id ) {
    update_post_meta( $post_id, 'wc_cpg_free_products', $_POST['wc_cpg_free_products'] );
}
add_action( 'woocommerce_coupon_options_save', 'wc_cpg_save_product_field' );

function wc_cpg_apply_coupon( $coupon_code ) {
    global $woocommerce;

    // Obtener el ID del cupón
    $coupon_id = wc_get_coupon_id_by_code( $coupon_code );

    // Obtener los productos gratuitos asociados con el cupón
    $free_products = get_post_meta( $coupon_id, 'wc_cpg_free_products', true );
    $free_products = explode( ',', $free_products );

    // Agregar los productos gratuitos al carrito
    foreach ( $free_products as $product_id ) {
        $woocommerce->cart->add_to_cart( $product_id );
        $woocommerce->cart->cart_contents[$woocommerce->cart->generate_cart_id($product_id)]['free_gift'] = true; // Marcar el producto como regalo
    }
}

add_action( 'woocommerce_applied_coupon', 'wc_cpg_apply_coupon' );

function wc_cpg_remove_coupon( $coupon_code ) {
    global $woocommerce;

    // Obtener el ID del cupón
    $coupon_id = wc_get_coupon_id_by_code( $coupon_code );

    // Obtener los productos gratuitos asociados con el cupón
    $free_products = get_post_meta( $coupon_id, 'wc_cpg_free_products', true );
    $free_products = explode( ',', $free_products );

    // Recorrer cada producto en el carrito
    foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $cart_item ) {

        // Si el producto está en la lista de productos gratuitos, quitarlo
        if ( in_array( $cart_item['product_id'], $free_products ) ) {
            $woocommerce->cart->remove_cart_item( $cart_item_key );
        }
    }
}
add_action( 'woocommerce_removed_coupon', 'wc_cpg_remove_coupon' );

// Aquí está el resto del código anterior

// Limit gift product quantity to 1 and disable remove option
add_filter('woocommerce_cart_item_remove_link', 'disable_gift_product_removal', 10, 2);
add_filter('woocommerce_cart_item_quantity', 'disable_gift_product_quantity_change', 10, 3);

function disable_gift_product_removal($link, $cart_item_key){
    if(isset(WC()->cart->cart_contents[$cart_item_key]['free_gift'])){
        $link = '';
    }
    return $link;
}

function disable_gift_product_quantity_change($product_quantity, $cart_item_key, $cart_item){
    if(isset($cart_item['free_gift'])){
        $product_quantity = sprintf('1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key);
    }
    return $product_quantity;
}

// Prevent quantity change
add_filter('woocommerce_before_cart_item_quantity_zero', 'prevent_gift_product_quantity_change', 10, 2);
function prevent_gift_product_quantity_change($passed, $cart_item_key){
    if(isset(WC()->cart->cart_contents[$cart_item_key]['free_gift'])){
        $passed = false;
        wc_add_notice(__('No puedes cambiar la cantidad del producto de regalo.', 'woocommerce'), 'error');
    }
    return $passed;
}

// Set the price of the gift product to $0
add_action('woocommerce_before_calculate_totals', 'set_gift_product_price', 10, 1);
function set_gift_product_price($cart_object){
    foreach($cart_object->get_cart() as $cart_item){
        if(isset($cart_item['free_gift'])){
            $cart_item['data']->set_price(0);
        }
    }
}

// Hook into woocommerce_after_calculate_totals to check the cart items
add_action('woocommerce_after_calculate_totals', 'remove_coupon_if_less_than_min_qty');

function remove_coupon_if_less_than_min_qty($cart) {
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    $applied_coupons = $cart->get_applied_coupons();
    if (empty($applied_coupons)) {
        return; // Return if no coupons have been applied
    }

    $regular_product_count = 0;
    $only_product_id = 4065; // ID of the product
    $only_product_exists = false;
    
    foreach($cart->get_cart() as $cart_item){
        if(!isset($cart_item['free_gift'])){
            $regular_product_count += $cart_item['quantity'];
            // Check if the only product is the one with the specific ID
            if ($cart_item['product_id'] == $only_product_id) {
                $only_product_exists = true;
            } else {
                $only_product_exists = false;
            }
        }
    }

    foreach ($applied_coupons as $coupon_code) {
        // Get the ID of the coupon
        $coupon_id = wc_get_coupon_id_by_code( $coupon_code );

        // Get the minimum quantity of products required for the coupon
        $min_product_qty = get_post_meta( $coupon_id, 'min_product_qty', true );

        // If the minimum product quantity is set and the regular product count is less than the minimum required
        // or if the only product is the one with the specific ID
        if ((!empty($min_product_qty) && $regular_product_count < $min_product_qty) || ($regular_product_count == 1 && $only_product_exists)) {
            // Removing the coupons
            $cart->remove_coupon($coupon_code);
            wc_add_notice( sprintf( __( 'El cupón "%s" ha sido removido ya que la cantidad de productos regulares en el carrito es menor que la requerida.', 'woocommerce' ), $coupon_code ), 'notice' );
        }
    }
}


?>
