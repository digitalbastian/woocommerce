<?php
/*
Plugin Name: Bastian Descuento por Comentario en WooCommerce
Description: Envía un cupón de descuento a los usuarios que dejen un comentario en un producto de WooCommerce.
Version: 1.0
Author: Bastian
*/

// No permitir el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se intenta acceder al archivo directamente
}

function enviar_cupon_descuento_por_comentario( $comment_ID, $comment_approved, $commentdata ) {
    if ( 'product' === get_post_type( $commentdata['comment_post_ID'] ) ) {
        
        $codigo_cupon = wp_generate_password( 8, false, false );

        // Definir los argumentos del cupón
        $cupon_data = array(
            'post_title'   => $codigo_cupon,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_type'    => 'shop_coupon'
        );
        
        // Insertar el cupón como un nuevo post
        $new_coupon_id = wp_insert_post( $cupon_data );

        if ($new_coupon_id) {
            // Establecer las propiedades del cupón
            update_post_meta( $new_coupon_id, 'discount_type', 'percent' );
            update_post_meta( $new_coupon_id, 'coupon_amount', 5 );
            update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
            update_post_meta( $new_coupon_id, 'usage_limit', 1 );

            // Establecer la fecha de caducidad del cupón (un mes después de la creación)
            $fecha_caducidad = date('Y-m-d', strtotime('+1 month'));
            update_post_meta( $new_coupon_id, 'expiry_date', $fecha_caducidad );

            // Enviar el cupón por correo
            $para = $commentdata['comment_author_email'];
            $asunto = '¡Gracias por tu comentario! Aquí tienes un descuento';
            $mensaje = "¡Hola! Gracias por dejar tu comentario. Aquí tienes un código de descuento del 5%: $codigo_cupon. Caduca el: $fecha_caducidad";
            wp_mail( $para, $asunto, $mensaje );

            // Redirigir al usuario a la página /tienda/
            wp_redirect( home_url('/gracias-por-tu-comentario/') );
            exit;

        } else {
            // En caso de error, simplemente redirigir al usuario a la página /tienda/
            wp_redirect( home_url('/tienda/') );
            exit;
        }
    }
}
add_action( 'comment_post', 'enviar_cupon_descuento_por_comentario', 10, 3 );
