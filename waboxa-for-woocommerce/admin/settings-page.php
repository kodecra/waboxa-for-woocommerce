<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Waboxa Mesajları',
        'Waboxa Mesajları',
        'manage_options',
        'waboxa-settings',
        'waboxa_admin_page',
        'dashicons-whatsapp'
    );
});

function waboxa_admin_page() {

    // ✅ Nonce kontrolü + güvenli veri işleme
    if (isset($_POST['waboxa_save']) && check_admin_referer('waboxa_save_action', 'waboxa_nonce')) {

        $messages = array(
            'alindi' => sanitize_textarea_field(
                wp_unslash(isset($_POST['alindi']) ? $_POST['alindi'] : '')
            ),
            'kargo'  => sanitize_textarea_field(
                wp_unslash(isset($_POST['kargo']) ? $_POST['kargo'] : '')
            ),
            'teslim' => sanitize_textarea_field(
                wp_unslash(isset($_POST['teslim']) ? $_POST['teslim'] : '')
            ),
        );

        update_option('waboxa_messages', $messages);

        echo '<div class="updated"><p>✅ Mesajlar kaydedildi.</p></div>';
    }

    $msgs = get_option('waboxa_messages', array());
    ?>
    <div class="wrap" style="font-family:Poppins,sans-serif;max-width:800px;">
        <h1>📦 Waboxa Mesaj Şablonları</h1>
        <p>Mesajlarda şu değişkenleri kullanabilirsiniz:</p>
        <ul>
            <li><code>{fullname}</code> → Müşteri adı</li>
            <li><code>{order_id}</code> → Sipariş numarası</li>
            <li><code>{courier_title}</code> → Kargo firması</li>
            <li><code>{tracking_url}</code> → Takip linki</li>
            <li><code>{review_link}</code> → Değerlendirme linki</li>
        </ul>

        <form method="post">
            <?php 
            // ✅ Nonce alanı eklendi
            wp_nonce_field('waboxa_save_action', 'waboxa_nonce');

            $fields = array(
                'alindi' => 'Alındı Mesajı',
                'kargo'  => 'Kargo Mesajı',
                'teslim' => 'Teslim Mesajı'
            );

            foreach ($fields as $k => $label) {
                echo '<h3>' . esc_html($label) . '</h3>';
                echo '<textarea name="' . esc_attr($k) . '" rows="6" style="width:100%;">' .
                    esc_textarea(isset($msgs[$k]) ? $msgs[$k] : '') .
                    '</textarea>';
            }
            ?>
            <p>
                <button type="submit" name="waboxa_save" class="button-primary">💾 Kaydet</button>
            </p>
        </form>
    </div>
    <?php
}
