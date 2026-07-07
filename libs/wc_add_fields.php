<?php

add_filter( 'woocommerce_states', 'bazara_woocommerce_states' );

function bazara_woocommerce_states( $states ) {

    $provinces = get_provinces();

    $args = array();
    foreach ($provinces as $province)
    {
        $args[$province->ProvinceName] = $province->ProvinceName;

    }
    $states['IR'] = $args;

    return $states;
}

