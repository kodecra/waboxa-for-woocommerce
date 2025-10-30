<?php
/*
Plugin Name: Waboxa for WooCommerce
Description: WooCommerce siparişleri için özelleştirilebilir WhatsApp mesaj butonları (alındı, kargo, teslim)
Version: 1.0.0
Author: Kodecra
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: waboxa-for-woocommerce
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// === ADMIN PANEL ===
require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';

// === SCRIPT ===
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_script(
        'waboxa-js',
        plugin_dir_url( __FILE__ ) . 'assets/waboxa.js',
        array(),
        '1.0.0',
        true
    );
});

// === MAIN BUTTONS ===
add_action( 'woocommerce_admin_order_data_after_order_details', function( $order ) {

    if ( ! $order instanceof WC_Order ) {
        return;
    }

    $phone = preg_replace( '/\D/', '', $order->get_billing_phone() );
    if ( empty( $phone ) ) {
        return;
    }
    if ( strpos( $phone, '90' ) !== 0 ) {
        $phone = '90' . $phone;
    }

    $order_id    = absint( $order->get_id() );
    $fullname    = sanitize_text_field( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) );
    $review_link = esc_url( site_url( '/my-account/orders/' ) );

    // === Kargo bilgileri (Hezarfen uyumlu) ===
    $courier_title = '';
    $tracking_num  = '';
    $tracking_url  = '';
    $hezarfen_data = $order->get_meta( '_hezarfen_mst_shipment_data', false );

    if ( ! empty( $hezarfen_data ) && is_array( $hezarfen_data ) ) {
        foreach ( $hezarfen_data as $shipment ) {
            $raw = ( is_object( $shipment ) && isset( $shipment->value ) ) ? $shipment->value : (string) $shipment;
            if ( empty( $raw ) ) {
                continue;
            }

            if ( strpos( $raw, '||' ) !== false ) {
                $parts = explode( '||', $raw );
                $courier_title = isset( $parts[3] ) ? sanitize_text_field( $parts[3] ) : '';
                $tracking_num  = isset( $parts[4] ) ? sanitize_text_field( $parts[4] ) : '';
                $tracking_url  = isset( $parts[5] ) ? esc_url_raw( $parts[5] ) : '';
            }
        }
    }

    if ( empty( $tracking_url ) && ! empty( $tracking_num ) ) {
        $tracking_url = esc_url( 'https://hepsijet.com/gonderi-takibi/' . rawurlencode( $tracking_num ) );
        if ( empty( $courier_title ) ) {
            $courier_title = 'HepsiJET';
        }
    }

    // === Admin panel mesajları ===
    $templates = get_option( 'waboxa_messages', array() );
    $defaults  = array(
        'alindi' => "✅ Merhaba {fullname},\n\nSiparişiniz (#{order_id}) başarıyla alındı 🎉",
        'kargo'  => "🚚 Merhaba {fullname},\n\nSiparişiniz (#{order_id}) {courier_title} ile kargoya verildi.\nTakip linki:\n{tracking_url}",
        'teslim' => "🎀 Merhaba {fullname},\n\nSiparişiniz (#{order_id}) teslim edildi.\nYorum bırakmak ister misiniz?\n{review_link}",
    );

    $msgs = array_merge( $defaults, $templates );

    echo '<div class="waboxa-box" style="margin-top:15px;padding:12px;border:1px solid #ddd;border-radius:8px;background:#f9f9f9;">';
    echo '<h3 style="margin-top:0;">📱 ' . esc_html__( 'WhatsApp Mesajı Gönder', 'waboxa-for-woocommerce' ) . '</h3>';

    foreach ( $msgs as $key => $msg ) {

        if ( empty( $msg ) ) {
            continue;
        }

        $final = strtr(
            $msg,
            array(
                '{fullname}'      => $fullname,
                '{order_id}'      => $order_id,
                '{courier_title}' => $courier_title,
                '{tracking_url}'  => $tracking_url,
                '{review_link}'   => $review_link,
            )
        );

        // Değişkenleri güvenli hale getir
        $safe_final = esc_attr( $final );
        $safe_label = esc_html( ucfirst( sanitize_text_field( $key ) ) );

        // ✅ Tüm escape işlemleri WPCS uyumlu
        printf(
            '<button type="button" class="button waboxa-btn" data-phone="%s" data-msg="%s" style="margin:4px 6px;background:#25D366;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-weight:500;">%s %s</button>',
            esc_attr( $phone ),
            esc_attr( $safe_final ), // 🔒 Çifte koruma: OutputNotEscaped uyarısı sıfırlandı
            esc_html( $safe_label ), // 🔒 Çifte koruma
            esc_html__( 'Mesajı', 'waboxa-for-woocommerce' )
        );
    }

    echo '</div>';
});
