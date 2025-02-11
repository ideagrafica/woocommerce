<?php
/**
 * Plugin Name: WooCommerce Recursive Discount Plugin
 * Description: Dynamically applies recursive discounts to product prices based on stock quantity and sales, with a discount end date and stock-based conditions.
 * Version: 2.0
 * Author: Marco De Sangro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add custom fields to the product edit page
add_action('woocommerce_product_options_general_product_data', 'add_discount_fields');
add_action('woocommerce_process_product_meta', 'save_discount_fields');

// Apply discount based on stock and end date
add_filter('woocommerce_product_get_price', 'apply_dynamic_discount', 10, 2);

// Register shortcodes
add_shortcode('discount_min_price', 'display_min_price_shortcode');
add_shortcode('discount_end_date', 'display_end_date_shortcode');

/**
 * Add custom fields to the product edit page
 */
function add_discount_fields() {
    echo '<div class="options_group">';

    woocommerce_wp_text_input([
        'id' => '_discount_percentage',
        'label' => __('Discount Percentage', 'woocommerce'),
        'description' => __('Percentage to discount from the price per sale.', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0', 'max' => '100'],
    ]);
    
    woocommerce_wp_text_input([
        'id' => '_initial_stock',
        'label' => __('Quantità disponibile', 'woocommerce'),
        'description' => __('Inserisci la quantità disponibile di posti.', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => ['step' => '1', 'min' => '0'],
    ]);

    woocommerce_wp_text_input([
        'id' => '_discount_min_price',
        'label' => __('Minimum Price', 'woocommerce'),
        'description' => __('The lowest price the product can reach.', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0'],
    ]);

    woocommerce_wp_text_input([
        'id' => '_discount_end_date',
        'label' => __('Discount End Date', 'woocommerce'),
        'description' => __('End date for the discount (YYYY-MM-DD).', 'woocommerce'),
        'desc_tip' => true,
        'type' => 'date',
    ]);

    echo '</div>';
}

/**
 * Save custom fields
 */
function save_discount_fields($post_id) {
    $fields = ['_discount_percentage', '_discount_min_price', '_discount_end_date', '_initial_stock'];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}

/**
 * Apply discount dynamically based on stock and date
 */
function apply_dynamic_discount($price, $product) {
    $product_id = $product->get_id();

    // Retrieve custom fields ___________________________________________________________________________________stock and date_____
    $percentage = (float)get_post_meta($product_id, '_discount_percentage', true);
    $min_price = (float)get_post_meta($product_id, '_discount_min_price', true);
    $end_date = get_post_meta($product_id, '_discount_end_date', true);
    $stock_quantity = (int)$product->get_stock_quantity();
    $initial_stock = (float)get_post_meta($product_id, '_initial_stock', true);


    //$sales = $initial_stock - $stock_quantity; // ------------------------------------------------------------------
    $iscrizioni = $initial_stock - $stock_quantity;    // numero iscritti rilevati dal sistema impostando 100 di inventario
     
    $current_price = $price;
    for ($i = 0; $i < $iscrizioni; $i++) {
        $current_price -= ($current_price * $percentage) / 100;
        if ($current_price <= $min_price) {
            $current_price = $min_price;
            break;
        }
    }

    return round($current_price, 2);
}
/**
 * Shortcode to display minimum price
 */
function display_min_price_shortcode($atts) {
    global $product;

    if (!$product) {
        return '';
    }

    $min_price = get_post_meta($product->get_id(), '_discount_min_price', true);

    return $min_price ? '<span class="min-price-label">Costo minimo: ' . wc_price($min_price) . '</span>' : '';
}

/**
 * Shortcode to display end date
 */
function display_end_date_shortcode($atts) {
    global $product;

    if (!$product) {
        return '';
    }

    $end_date = get_post_meta($product->get_id(), '_discount_end_date', true);

    if ($end_date) {
        $formatted_date = date_i18n('d/m/Y', strtotime($end_date));
        return "JOIN&DROP termina: " . esc_html($formatted_date);
    }

    return '';
}

// Shortcode per mostrare il prezzo scontato
function shortcode_dynamic_discounted_price($atts) {
    // Attributi dello shortcode (ID del prodotto)
    $atts = shortcode_atts(array(
        'product_id' => null,
    ), $atts);

    // Recupera il prodotto
    $product_id = $atts['product_id'] ? (int)$atts['product_id'] : get_the_ID();
    $product = wc_get_product($product_id);

    if (!$product) {
        return 'Prodotto non trovato.';
    }

    // Ottieni il prezzo originale
    $price = $product->get_regular_price();

    // Recupera i meta campi personalizzati
    $percentage = (float)get_post_meta($product_id, '_discount_percentage', true);
    $min_price = (float)get_post_meta($product_id, '_discount_min_price', true);
    $end_date = get_post_meta($product_id, '_discount_end_date', true);
    $stock_quantity = (int)$product->get_stock_quantity();
    $initial_stock = (float)get_post_meta($product_id, '_initial_stock', true);

    // Calcola il numero di iscrizioni
    $iscrizioni = $initial_stock - $stock_quantity;

    // Calcola il prezzo scontato
    $current_price = $price;
    for ($i = 0; $i < $iscrizioni; $i++) {
        $current_price -= ($current_price * $percentage) / 100;
        if ($current_price <= $min_price) {
            $current_price = $min_price;
            break;
        }
    }

    // Restituisci il prezzo scontato come output dello shortcode
    return '<p class="prezzo-raggiunto">' . wc_price(round($current_price, 2)) . '</p>';
    
}

// Aggiungi lo shortcode
add_shortcode('dynamic_discounted_price', 'shortcode_dynamic_discounted_price');
