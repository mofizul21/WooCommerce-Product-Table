<?php

/*
Plugin Name: Product Table
Plugin URI: https://github.com/
Description: A plugin to display a product table with select all functionality.
Version: 1.0
Author: ThemePackNet
Author URI: https://themepack.net
License: GPLv2 or later
Text Domain: mo-product-table
Domain Path: /languages/
*/


// Include additional functionality
//include_once plugin_dir_path(__FILE__) . 'includes/mailtrap.php';

// Function to check if WooCommerce is not installed and display a notice
function check_woocommerce_installed() {
    // Check if WooCommerce is not active
    if (!class_exists('WooCommerce')) {
        // Hook to display admin notice
        add_action('admin_notices', 'woocommerce_missing_notice');
    }
}
// Hook into WordPress admin initialization
add_action('admin_init', 'check_woocommerce_installed');

// Function to display the notice
function woocommerce_missing_notice() {
?>
    <div class="notice notice-error">
        <p><?php _e('WooCommerce is not installed or activated. Please install and activate WooCommerce to make workable the Product Table.', 'your-text-domain'); ?></p>
    </div>
<?php
}

// Enqueue scripts and styles
function mo_product_table_scripts() {
    wp_enqueue_script('mo-product-table-js', plugin_dir_url(__FILE__) . 'assets/js/mo-product-table.js', array('jquery'), time(), true);
    wp_enqueue_style('mo-product-table-css', plugin_dir_url(__FILE__) . 'assets/css/mo-product-table.css', array(), '1.0');

    // Get cart items
    $cart_items = WC()->cart->get_cart();
    $cart_product_ids = array();
    foreach ($cart_items as $item) {
        $cart_product_ids[] = $item['product_id'];
    }

    wp_localize_script('mo-product-table-js', 'ajax_object', array(
        'ajax_url'   => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('add_to_cart_nonce'),
        'cart_items' => $cart_product_ids,
    ));
}

add_action('wp_enqueue_scripts', 'mo_product_table_scripts');

// Shortcode function to display the product table
function mo_product_table() {
    // Check if WooCommerce is active before proceeding
    if (!class_exists('WooCommerce')) {
        echo '<p>WooCommerce is not installed or activated. Please install and activate WooCommerce.</p>';
        return;
    }

?>
    <div class="directions">
        <h2>Directions and information</h2>
        <ul>
            <li>All products are free.</li>
            <li>You can order a product only one time.</li>
            <li>Some products are grouped. You can buy only a product from a group.</li>
        </ul>
    </div>
    <div class="allCheckCartHolder">
        <div class='leftSide selectAll'>
            <input type="checkbox" id="select-all-top"> <label for="select-all-top">SELECT ALL</label>
        </div>
        <div class='rightSide addToCartHolder'>
            Add to Cart
        </div>
    </div>
    <table id="product-table">
        <tbody>
            <?php
            // Fetch products
            $args = array('post_type' => 'product', 'posts_per_page' => -1);
            $products = new WP_Query($args);

            if ($products->have_posts()) {
                echo '<table id="product-table">';
                echo '<thead><tr><th>#</th><th><input type="checkbox" class="select-all-product" /></th><th>Title</th><th>Category</th><th>SKU</th><th>Add to Cart</th></tr></thead>';
                echo '<tbody>';

                $index = 1;
                while ($products->have_posts()) : $products->the_post();
                    global $product;
                    // Ensure the global $product is set correctly
                    $product = wc_get_product(get_the_ID());
                    // Get product price
                    $price = $product->get_price();

                    // Get product categories
                    $categories = get_the_terms($product->get_id(), 'product_cat');
                    $category_names = wp_list_pluck($categories, 'name');
                    $category_list = implode(', ', $category_names);

                    if ($product) {
                        echo '<tr>';
                        echo '<td>' . $index . '</td>';
                        echo '<td><input type="checkbox" class="select-product" data-product-id="' . $product->get_id() . '"></td>';
                        echo '<td>' . get_the_title() . '</td>';
                        //echo '<td>' . wc_price($price) . '</td>';
                        echo '<td>' . $category_list . '</td>';
                        echo '<td>' . $product->get_sku() . '</td>';
                        echo '<td><button class="add-to-cart" data-product-id="' . $product->get_id() . '">Add to Cart</button></td>';
                        echo '</tr>';
                        $index++;
                    }
                endwhile;
                wp_reset_postdata();

                echo '</tbody></table>';
                echo '</div>';
            } else {
                echo 'No products found.';
            }
            ?>
        </tbody>
    </table>
<?php
}
add_shortcode('mo_product_table', 'mo_product_table');


// Handle single product add to cart
function add_product_to_cart() {
    check_ajax_referer('add_to_cart_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);

    if ($product_id > 0) {
        $added = WC()->cart->add_to_cart($product_id);

        if ($added) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_add_to_cart', 'add_product_to_cart');
add_action('wp_ajax_nopriv_add_to_cart', 'add_product_to_cart');

// Handle bulk add to cart
function bulk_add_products_to_cart() {
    check_ajax_referer('add_to_cart_nonce', 'nonce');

    $product_ids = isset($_POST['product_ids']) ? (array) $_POST['product_ids'] : array();

    if (!empty($product_ids)) {
        $added = false;
        foreach ($product_ids as $product_id) {
            if (WC()->cart->add_to_cart(intval($product_id))) {
                $added = true;
            }
        }

        if ($added) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to add products to cart.');
        }
    } else {
        wp_send_json_error('No products selected.');
    }
}
add_action('wp_ajax_bulk_add_to_cart', 'bulk_add_products_to_cart');
add_action('wp_ajax_nopriv_bulk_add_to_cart', 'bulk_add_products_to_cart');

// Handle remove product from cart
function remove_product_from_cart() {
    check_ajax_referer('add_to_cart_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);

    $cart = WC()->cart->get_cart();
    foreach ($cart as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $product_id) {
            WC()->cart->remove_cart_item($cart_item_key);
            wp_send_json_success();
            return;
        }
    }

    wp_send_json_error();
}
add_action('wp_ajax_remove_from_cart', 'remove_product_from_cart');
add_action('wp_ajax_nopriv_remove_from_cart', 'remove_product_from_cart');

