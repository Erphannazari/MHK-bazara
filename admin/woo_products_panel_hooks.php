<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


add_action( 'woocommerce_product_write_panel_tabs', 'mahak_tab_action' );

function mahak_tab_action() {
    ?>
    <li class="mahak_tab">
        <a href="#mahak_tab_panel">
            <?php _e( 'اطلاعات محک', 'textdomain' ); ?>
        </a>
    </li>

    <?php
}

add_action( 'woocommerce_product_data_panels', 'mahak_tab_panel' );

function mahak_tab_panel() {
    ?>
    <div id="mahak_tab_panel" class="panel woocommerce_options_panel">
        <div class="options_group">
            <?php


            $fields = array(
                array(
                    'id' => 'mahak_id',
                    'name' => 'mahak_id',
                    'label' => __( 'کد کالا', 'textdomain' ),
                ),
                array(
                    'id' => 'mahak_product_store_id',
                    'name' => 'mahak_product_store_id',
                    'label' => __( 'کد انبار', 'textdomain' ),
                ),
                array(
                    'id' => 'mahak_product_tax',
                    'name' => 'mahak_product_tax',
                    'label' => __( 'مالیات', 'textdomain' ),
                ),
                array(
                    'id' => 'mahak_product_charge',
                    'name' => 'mahak_product_charge',
                    'label' => __( 'عوارض', 'textdomain' ),
                )
            );
            foreach ($fields as $field)
            {

                woocommerce_wp_text_input( $field );
            }
            woocommerce_wp_checkbox( array(
                'id'            => 'mahak_custom_date',
                'name' => 'mahak_custom_date',
                'label'         => __( 'فروش کالا با نزدیکترین تاریخ انقضا' ),
                'description'   => __( ' با برداشتن این تنظیم امکان نمایش و انتخاب سایر تاریخ مصرف های موجود میسر می شود' )
            ) );



            ?>
        </div>
    </div>
    <?php
}

add_action( 'woocommerce_process_product_meta', 'save_mahak_fields' );

function save_mahak_fields( $post_id )
{

   $fields = array(
       'mahak_id' => isset($_POST['mahak_id']) ? $_POST['mahak_id'] : '',
       'store_id' => isset($_POST['store_id']) ? $_POST['store_id'] : '',
       'mahak_product_tax' => isset($_POST['mahak_product_tax']) ? $_POST['mahak_product_tax'] : '',
       'mahak_product_charge' => isset($_POST['mahak_product_charge']) ? $_POST['mahak_product_charge'] : '',
       'mahak_custom_date' => isset($_POST['mahak_custom_date']) ? 'yes' : 'no',

   );
   $customDate = get_post_meta($post_id,'mahak_custom_date',true);

   if (isset($_POST['mahak_id']) && isset($_POST['mahak_custom_date']) <> $customDate)
   {
        $pid = $_POST['mahak_id'];
        update_schedule_sync($pid,'detailSync',0);
        update_schedule_sync($pid,'stockSync',0);
        update_schedule_sync($pid,'priceSync',0);
   }

   $product = wc_get_product($post_id);
   foreach ($fields as $key => $value)
   {

       $product->update_meta_data($key, $value);
   }
   $product->save();
}
add_action( 'woocommerce_variation_options_pricing', 'bazara_woo_add_custom_general_fields' , 10, 3);

function bazara_woo_add_custom_general_fields($loop, $variation_data, $variation ) {

    $product_detail_id      = get_post_meta( $variation->ID, 'mahak_product_detail_id', true );
    $product_date     = get_post_meta( $variation->ID, 'mahak_custom_date', true );

    echo '<div class="variation_price_rule">
		<label></label>';

    echo '<p class="form-field variable_regular_price_0_field form-row form-row-first">
		<label for="mahak_product_detail_id">کد جزئیات کالا</label>
		<input type="text" class="short wc_input_price" style="" name="mahak_product_detail_id" id="mahak_product_detail_id" value="' . $product_detail_id .'"  placeholder="کد گروه کالا ر وارد نمایید">
		</p>';

    echo '</div>';


}
add_action( 'woocommerce_save_product_variation', 'save_mahak_product_variation', 25, 2 );

function save_mahak_product_variation( $variation_id, $i ) {
    // Text Field
    $text_field = $_POST['mahak_product_detail_id'][ $variation_id ];
    if( ! empty( $text_field ) ) {
        update_post_meta( $variation_id, 'mahak_product_detail_id', esc_attr( $text_field ) );
    }
    
}
