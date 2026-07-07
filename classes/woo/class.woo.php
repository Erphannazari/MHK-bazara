<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Bazara_WOO
{
    public static $instance = null;
    public function __construct()
    {
        add_action('wp_ajax_bazara_woocommerce_ajax_add_to_cart', [$this,'bazara_woocommerce_ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_bazara_woocommerce_ajax_add_to_cart', [$this,'bazara_woocommerce_ajax_add_to_cart']);
        add_action('wp_enqueue_scripts', [$this,'woocommerce_ajax_add_to_cart_js'], 99);
        add_action( 'woocommerce_before_single_variation', [$this,'bazara_add_radio_input'], 20 );
        add_action( 'woocommerce_before_calculate_totals', [$this,'bazara_add_custom_price'],999 );
        add_filter( 'woocommerce_shipping_method_add_rate_args', [$this,'bazara_shipping_extra_per_product'], 999, 2 );
        add_filter('woocommerce_product_get_regular_price', array( &$this, 'bazara_get_regular_price' ), 99, 2);
        add_filter('woocommerce_product_get_sale_price', array( &$this, 'bazara_get_regular_price' ), 99, 2);
        add_filter('woocommerce_product_get_price', array( &$this, 'bazara_get_regular_price' ), 99, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array( &$this, 'bazara_get_parent_regular_price' ), 99, 2);
        add_filter('woocommerce_product_variation_get_sale_price', array( &$this, 'bazara_get_parent_regular_price' ), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array( &$this, 'bazara_get_parent_regular_price' ), 99, 2);
        add_action( 'woocommerce_thankyou', array( &$this,'b_woocommerce_thankyou'), 10, 1 );
        add_action( 'woocommerce_remove_cart_item', array( &$this,'bazara_cart_updated'), 10, 2 );
        add_action( 'woocommerce_before_single_product', array( &$this,'bazara_add_cart_quantity_plus_minus') );
     


    }
   
    function bazara_add_cart_quantity_plus_minus() {
   wc_enqueue_js( "
      $('form.cart').on( 'click', 'button.plus, button.minus', function() {
            var qty = $( this ).closest( 'form.cart' ).find( '.qty' );
            var val   = parseFloat(qty.val());
            var max = parseFloat(qty.attr( 'max' ));
            var min = parseFloat(qty.attr( 'min' ));
            var step = parseFloat(qty.attr( 'step' ));
            if ( $( this ).is( '.plus' ) ) {
               if ( max && ( max <= val ) ) {
                  qty.val( max );
               } else {
                  qty.val( val + step );
               }
            } else {
               if ( min && ( min >= val ) ) {
                  qty.val( min );
               } else if ( val > 1 ) {
                  qty.val( val - step );
               }
            }
         });
   " );
}

    function bazara_cart_updated( $cart_item_key, $cart ) {
        
        unset($_SESSION['bazara_set_wholesale_purchase']);

    }
    function b_woocommerce_thankyou( $order_id ) {
        if(!empty($order_id))
            return;
        unset($_SESSION['bazara_set_wholesale_purchase']);
    }
       public function bazara_get_regular_price($price, $product) {
        session_start();

        if (isset($_SESSION['bazara_set_wholesale_purchase']) && $_SESSION['bazara_set_wholesale_purchase'] ){

        $getProductPrice = get_post_meta( $product->get_id(), '_role_based_price', true);
             if (is_array($getProductPrice) && !empty($getProductPrice[BAZARA_WCPDF_USER_WHOLESALER])){
                $price = $getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]['regular_price'];
        }
    }
        return $price;
    }
    public function bazara_get_parent_regular_price($price, $product) {
        session_start();

        if (isset($_SESSION['bazara_set_wholesale_purchase']) && $_SESSION['bazara_set_wholesale_purchase'] ){

        $getProductPrice = get_post_meta( $product->get_parent_id(), '_role_based_price', true);
             if (is_array($getProductPrice) && !empty($getProductPrice[BAZARA_WCPDF_USER_WHOLESALER])){
                $price = $getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]['regular_price'];
        }
    }
        return $price;
    }
    function bazara_shipping_extra_per_product( $args, $shipping_method ) {
        session_start();

        if (isset($_SESSION['bazara_set_wholesale_purchase']) && $_SESSION['bazara_set_wholesale_purchase'] ){

        foreach ( WC()->cart->get_cart() as $key => $value) {
            if ( isset( $value['data'] )) {
		$getProductPrice = get_post_meta( $value['data']->get_parent_id(), '_role_based_price', true);
             if (is_array($getProductPrice) && !empty($getProductPrice[BAZARA_WCPDF_USER_WHOLESALER])){
             $custom_price = $getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]['regular_price'];
            $value['data']->set_price($custom_price);
                
        }
            
                if ($shipping_method && $args['cost'] > 0) {
	        $quantity = $value['quantity'];

                    $args['cost'] += ($quantity * 1500);
                }
            }
        }
    }
	return $args;
    }
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    function woocommerce_ajax_add_to_cart_js() {
        if (function_exists('is_product') && is_product()) {
             wp_enqueue_script('woocommerce-ajax-add-to-cart', BAZARA_PATH . 'assets/js/ajax-add-to-cart.js', array('jquery'), '1.22', true);
        }
        
    }
    function bazara_add_custom_price( $cart_object ) {
        session_start();

        if (isset($_SESSION['bazara_set_wholesale_purchase']) && $_SESSION['bazara_set_wholesale_purchase'] ){

        foreach ( $cart_object->cart_contents as $key => $value ) {
             $getProductPrice = get_post_meta( $value['data']->get_parent_id(), '_role_based_price', true);
             if (is_array($getProductPrice) && !empty($getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]))
             $custom_price = $getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]['regular_price'];
            else continue;
            $value['data']->set_price($custom_price);
             $value['line_total'] = $custom_price;

        }
    }
        return $cart_object;
    }
    function bazara_add_radio_input() {
        echo '
        <span style="float:right;margin-left:10px;font-size:18px"><input type="checkbox" id="whole_sale_radio" name="whole_sale_radio" value="whole_sale_radio"/>  خرید عمده (جین)</span>
        <div class="bazara_d" style="display: flex;display:none"> <div class="quantity-con">
		<button type="button" class="plus">
   			<i class="fal fa-plus"></i>
		</button>
		<div class="quantity">
		<input type="text" id="bazara_quantity" class="input-text qty text"   name="bazara_quantity" pattern="^\d*(\.\d{0,2})?$" step="0.5" value="0.5" title="تعداد جین" size="4">
		</div>
		<button type="button" class="minus">
			<i class="fal fa-minus"></i>
		</button>
	</div>
    <button type="submit" class="single_add_to_cart_button2 button alt wp-element-button is-addable">افزودن به سبد خرید</button></div>
   ';
    }
   
    function bazara_woocommerce_ajax_add_to_cart() {
    $id = absint($_POST['product_id']);
    $floatQty = floatval($_POST['floatQty']);

    $product_id = apply_filters('woocommerce_add_to_cart_product_id', $id);
    $product = wc_get_product($id);
    $variations = $product->get_available_variations();
    $data = $res = [];
    
    $sell = 0;
    $pos = 0;
    foreach($variations as $variation){
        $stock = get_post_meta($variation['variation_id'], "_stock", true);
        $res['variation_id'] = $variation['variation_id'];
        $res['stock'] = $stock;

        $data[]= $res;
    }
    $jeanQty = ($floatQty > 0 ? $floatQty : 0.5 ) / .5;
    $calcJean = count($data) / 2;
    $everyJean = ($calcJean > 6 ? $calcJean : 6) * $jeanQty;
    loop:
    foreach($data as $variation){
    
    $stock = $variation['stock'];
    $quantity = empty($stock) ? 0 : wc_stock_amount($stock);
    $sum = array_sum(array_column($data,'stock'));
    if ($sum <= 0 || $everyJean == $sell) break;
    if ($quantity == 0) continue;
    
    $sell++;
    $quantity = 1;
        $data[$pos]['stock'] -= 1;  
    $variation_id = variation['variation_id'];
    $variation_id = absint($variation['variation_id']);
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
    $product_status = get_post_status($product_id);
    $getProductPrice = get_post_meta( $product_id, '_role_based_price', true);
    if (is_array($getProductPrice) && !empty($getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]))
    $custom_price = $getProductPrice[BAZARA_WCPDF_USER_WHOLESALER]['regular_price'];
    $cart_item_data = array('custom_price' => $custom_price); 
    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $cart_item_data) && 'publish' === $product_status) {

        // continue;
        if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
            wc_add_to_cart_message(array($product_id => $quantity), true);
        }
        do_action('woocommerce_ajax_added_to_cart', $variation_id);

        
    } else {

        $data = array(
            'error' => true,
            'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id));

        }
        $pos++;
    }

    if (array_sum(array_column($data,'stock')) > 0 && $sell < $everyJean)goto loop;
    session_start();
    $_SESSION['bazara_set_wholesale_purchase'] = true;    
    WC_AJAX :: get_refreshed_fragments();
    wp_die();
 }
}
Bazara_WOO::instance();