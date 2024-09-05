// Mini Cart shortcode

function enqueue_edd_mini_cart_scripts()
{
    wp_enqueue_script('edd-mini-cart', FL_CHILD_THEME_URL . '/js/mini-cart.js', array('jquery'), '1.1.0', true);

    wp_localize_script('edd-mini-cart', 'wpAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('update_mini_cart_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_edd_mini_cart_scripts');


/**
 * Displays the mini cart icon and quantity.
 *
 * This function renders the mini cart icon along with the current 
 * quantity of items in the cart. It is used to display a cart icon in 
 * the header or navigation menu and provides a visual indicator of 
 * the number of products currently in the cart.
 *
 */
function bb_mini_cart_icon()
{
    ob_start(); // Capture the output
?>
    <div class="edd-cart">
        <div class="edd-cart-icon">
        <i class="fab fa-opencart"></i>
        <i class="fas fa-shopping-cart"></i>
             <!-- <img src="<?php //echo get_stylesheet_directory_uri() . '/images/shopping-cart.png'; ?>" alt=""> -->
            <span class="header-cart edd-cart-quantity"><?php echo edd_get_cart_quantity(); ?></span>
        </div>
    </div>

    <div class="shopping-cart">
        <div class="loading-container">
            <div class="overlay"></div>
            <div class="loading-progress"></div>

            <div class="shopping-cart-items">
            </div>
            <p class="button cart-total-price">0</p>
            <a href="/checkout" class="button">Checkout <i class="fa fa-chevron-right"></i></a>
        </div>
    </div>
<?php
    return ob_get_clean();
}
add_shortcode('edd_cart_icon', 'bb_mini_cart_icon');

/**
 * Updates and displays the mini cart contents via AJAX.
 *
 * This function is responsible for rendering the contents of the mini cart, 
 * including product details such as the product thumbnail, name, quantity, 
 * and total price. It is triggered via AJAX when the cart is updated.
 *
 */
function bb_update_mini_cart()
{

    $cart_items = edd_get_cart_contents();
    
    $total_price = 0;
    $total_quantity = 0;
    $html = '';
    if ($cart_items) {
        $grouped_cart_items = [];

        foreach ($cart_items as $item) {
            $product_id = isset($item['id']) ? $item['id'] : 0;

            if (!isset($grouped_cart_items[$product_id])) {
                $grouped_cart_items[$product_id] = $item;
            } else {
                $grouped_cart_items[$product_id]['quantity'] += $item['quantity'];
            }
        }
          
        foreach ($grouped_cart_items as $product_id => $item) {

            $item_thumbnail = get_the_post_thumbnail($product_id, 'thumbnail');
            $item_name = isset($item['name']) ? $item['name'] : edd_get_cart_item_name($item);
            $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $item_unit_price = edd_get_cart_item_price($item['id']);
            $item_total_price = $item_unit_price * $item_quantity;

            $total_price += $item_total_price;
            $total_quantity += $item_quantity;

            $html .= '<div class="edd-cart-item">';
            $html .= '<a href="#" id="remove-cart-item" data-item-id="' . esc_attr($product_id) . '"><img src="' . FL_CHILD_THEME_URL . '/images/cross.png" alt="close-icon"></a>';
            $html .= '<div class="item-thumbnail">' . $item_thumbnail . '</div>';
            $html .= '<span class="item-name">' . esc_html($item_name) . '</span>';
            $html .=  '<span class="item-quantity">Qty: ' . esc_html($item_quantity) . '<br></span>';
            $html .= '<span class="item-price"> Total Price: ' . edd_currency_filter(edd_format_amount($item_total_price)) . '</span>';
            $html .=  '</div>';
        }
    } else {
        $html .=  '<p>Your cart is empty.</p>';
    }

    wp_send_json_success(array(
        'mini_cart' => $html,
        'total_price' => floor($total_price * 100) / 100,
        'cart_quantity' => $total_quantity
    ));
}
add_action('wp_ajax_update_mini_cart', 'bb_update_mini_cart');
add_action('wp_ajax_nopriv_update_mini_cart', 'bb_update_mini_cart');


/**
 * Removes an item from the mini cart.
 *
 * This function is responsible for removing a specific item from the mini cart
 * when the user clicks the "cross icon". It receives the item ID as a POST parameter
 * and removes the item from the cart.
 *
 */

 function bb_remove_from_cart() {
    // Get the item_id from the AJAX request
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $total_quantity = 0;
    if ($item_id > 0) {
        // Get the current cart contents
        $cart_items = EDD()->session->get('edd_cart');

        $updated_cart_items = [];

        // Loop through cart items to find the matching product ID
        foreach ($cart_items as $key => $item) {
            if ($item['id'] == $item_id) {
                // Skip this item to "remove" it from the cart
                continue;
            }
            // Add the remaining items back to the updated cart
            $updated_cart_items[$key] = $item;
        }
        // Update the cart session with the modified cart
         EDD()->session->set('edd_cart', $updated_cart_items);
        // Now rebuild the cart HTML and total price
        $total_price = 0;
        $html = '';

        if (!empty($updated_cart_items)) {
            foreach ($updated_cart_items as $item) {
                $product_id = isset($item['id']) ? $item['id'] : 0;
                $item_thumbnail = get_the_post_thumbnail($product_id, 'thumbnail');
                $item_name = isset($item['name']) ? $item['name'] : edd_get_cart_item_name($item);
                $item_quantity = isset($item['quantity']) ? $item['quantity'] : 1;

                $item_unit_price = edd_get_cart_item_price($item['id']);
                $item_total_price = $item_unit_price * $item_quantity;

                $total_price += $item_total_price;
                $total_quantity += $item_quantity;

                // Construct HTML for the mini cart
                $html .= '<div class="edd-cart-item edd-cart-item-'. $product_id . '">';
                $html .= '<a href="#" id="remove-cart-item" data-item-id="' . esc_attr($product_id) . '"><img src="' . FL_CHILD_THEME_URL . '/images/cross.png" alt="close-icon"></a>';
                $html .= '<div class="item-thumbnail">' . $item_thumbnail . '</div>';
                $html .= '<span class="item-name">' . esc_html($item_name) . '</span>';
                $html .= '<span class="item-quantity">Qty: ' . esc_html($item_quantity) . '<br></span>';
                $html .= '<span class="item-price"> Total Price: ' . edd_currency_filter(edd_format_amount($item_total_price)) . '</span>';
                $html .= '</div>';
            }
        } else {
            $html .= '<p>Your cart is empty.</p>';
        }

        // Send success response with the updated cart and total price
        wp_send_json_success(array(
            'mini_cart'   => $html,
            'total_price' => edd_format_amount($total_price, false),
            'cart_quantity' => $total_quantity
        ));
    } else {
        wp_send_json_error(array('message' => 'Invalid item ID'));
    }
}
add_action('wp_ajax_bb_remove_from_cart', 'bb_remove_from_cart');
add_action('wp_ajax_nopriv_bb_remove_from_cart', 'bb_remove_from_cart');