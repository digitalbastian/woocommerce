
<?php

/*
Plugin Name: Añade descuentos a todos los products en general
Description: Todos los productos tendran un descuento espesifico
Version: 1.0
Author: Digital Bastian
*/

add_action( 'woocommerce_cart_calculate_fees', 'descuento_por_monto_y_producto_especifico', 20, 1 );

function descuento_por_monto_y_producto_especifico( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $descuento_monto = 0;
    $descuento_producto = 0;
    $total = 0;
    $cantidad_id_12 = 0;
    $porcentaje_descuento_monto = 0;

    // Comprueba la cantidad de productos con ID 12 en el carrito y calcula el total sin ese producto
    foreach( $cart->get_cart() as $cart_item ){
        if( $cart_item['product_id'] == 23285 ) {
            $cantidad_id_12 += $cart_item['quantity'];
        } else {
            $total += $cart_item['line_subtotal'];
        }
    }

    // Descuentos por montos
    if ( $total < 1500 ) {
        $descuento_monto = $total * 0.05;
        $porcentaje_descuento_monto = 5;
    } elseif ( $total >= 1500 && $total < 2000 ) {
        $descuento_monto = $total * 0.10;
        $porcentaje_descuento_monto = 10;
    } elseif ( $total >= 2000 && $total < 3000 ) {
        $descuento_monto = $total * 0.20;
        $porcentaje_descuento_monto = 20;
    } elseif ( $total >= 3000 ) {
        $descuento_monto = $total * 0.25;
        $porcentaje_descuento_monto = 25;
    }

    // Descuento adicional por productos con ID 12
    if( $cantidad_id_12 > 0 ){
        $descuento_producto = (50/100) * intdiv($cantidad_id_12, 2) * wc_get_product( 23285 )->get_price();
    }

    // Aplica el descuento
    if ( $descuento_monto > 0 ) {
        $cart->add_fee( 'Hot sale Dalmir '. $porcentaje_descuento_monto . '%', -$descuento_monto );
    }
    if ( $descuento_producto > 0 ) {
        $cart->add_fee( 'Book Dalmir al 50%', -$descuento_producto );
    }
}

