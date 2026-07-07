<?php /* Variant DropDown menu changes */
if ( bazara_wc_version_check( '3.0' ) ) {
    add_filter( 'woocommerce_product_get_default_attributes', 'bazara_default_attribute', 10, 1 );
    add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'bazara_attribute_v2', 20, 2 );
    add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', 'bazara_hide_out_of_stock_option' );

} else {
    add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'bazara_attribute_args', 10, 1 );
}

function bazara_check_wc_version() {
    //Checking if get_plugins is available.
    if( !function_exists( 'get_plugins' ) ) {
      require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
  
    //Adding required variables
    $woo_folder = get_plugins( '/' . 'woocommerce' );
    $woo_file = 'woocommerce.php';
  
    //Checking if Version number is set.
    if( isset( $woo_folder[$woo_file]['Version'] ) ) {
      return $woo_folder[$woo_file]['Version'];
    } else {
      return NULL;
    }
  
  }
function bazara_wc_version_check( $version = '3.0' ) {
	
	$version_number = bazara_check_wc_version();
	
	if ( $version_number >= $version ) {
		return true;
	}
	
	return false;
}
/**
 * Re-order the variations depending on the chosen option. If nothing is set default to ID.
 *
 * @param array $args
 *
 * @return array
 */
function bazara_attribute_args( $args = array() ) {
    $sortby =  'stock';

    $product   = $args['product'];
    $attribute = strtolower( $args['attribute'] );

    $product_class = new WC_Product_Variable( $product );
    $children = $product_class->get_visible_children();
    $i = 0;
    if ( !empty( $children ) ) {
        foreach ( $children as $child ) {
            $required      = 'attribute_' . $attribute;
            $meta          = get_post_meta( $child );
            $to_use        = $meta[ $required ][ 0 ];
            $product_child = new WC_Product_Variation( $child );
            $prices[ $i ]  = array( $product_child->get_price(), $to_use );
            $i ++;
        }

      
        $args[ 'selected' ] = $prices[ 0 ][ 1 ];

        $args[ 'show_option_none' ] = '';
    }

    return $args;

}

/**
 * Remove the Choose an Option text - if applicable
 *
 * @param $args
 *
 * @return array|mixed|string|string[]
 */

function bazara_attribute_v2( $args ) {
    if ( get_option('hpy_disabled_auto_remove_dropdown') != 'yes' ) {
        $args['show_option_none'] = false;
    }

    return $args;
}

function bazara_default_attribute( $defaults ) {
   global $product;

    if ( !$product ) {
        return $defaults;
    }

    if ( $product->post_type !== 'product' ) {
        return $defaults;
    }

    $respect = 'no';
    $sortby = apply_filters( 'bazara_custom_sortby',  'stock' );
    $thensort = apply_filters( 'bazara_custom_then_sortby', 'menu_order' );
    $hide_oos = 'yes' == get_option( 'woocommerce_hide_out_of_stock_items' );
    $first_attribute = '';

    if ( $respect == 'yes' && !empty( $defaults ) ) {
        return $defaults;
    }

    if ( empty( $sortby ) ) {
        $sortby = 'stock';
    }

    if ( !$product->is_type( 'variable' ) ) {
        return $defaults;
    }

    $children = $product->get_children();
    $attributes = array();

    foreach( $children as $key => $child ) {
        $_child = wc_get_product( $child );
        $position = array_search( $key, array_keys( $children ) );
        $menu_order = array();
        $stock_qty = $_child->get_stock_quantity();
        $sales = $_child->get_total_sales();
        $stock_status = $_child->is_in_stock();
        $attrs = $_child->get_attributes();
        foreach( $attrs as $akey => $attr ) {
            $pattrs = explode( ',', str_replace( array( ', ', ' | ', '|' ), ',', strtolower( $product->get_attribute( $akey ) ) ) );
            if ( empty( $first_attribute ) ) {
                $first_attribute = $akey;
            }
            $menu_order[$akey] = array_search( strtolower( $attr ), $pattrs );
        }

        if ( $hide_oos && $stock_qty <=0 ) {
            //If Hide out of Stock is set, and this variant is out of stock, then skip.
            continue;
        }

        if ( $_child->get_status() == 'publish' ) {
            $attributes[] = apply_filters( 'bazara_build_attribute_filter', array( 'price' => !empty($_child->get_price()) ? $_child->get_price() : '0' , 'id' => $_child->get_id(), 'position' => $position, 'sales' => $sales, 'stock_level' => $stock_qty, 'menu_order' => $menu_order ) );
        }
    }

    $secondary_sort = false;

    switch( $sortby ) {

        case 'price-low':
            $secondary_sort = true;
            $attributes = bazara_multidimensional_sort( $attributes, 'price-low' );
            break;
        case 'price-high':
            $secondary_sort = true;
            $attributes = bazara_multidimensional_sort( $attributes, 'price-high' );
            break;

        case 'position':
            $attributes = bazara_multidimensional_sort( $attributes, 'position' );
            break;
        case 'id' :
            $attributes = bazara_multidimensional_sort( $attributes, 'id' );
            break;
        case 'menu_order':
            $attributes = bazara_multidimensional_sort( $attributes, 'menu_order', $first_attribute );
            break;

        default:
            $secondary_sort = apply_filters( 'bazara_do_secondary_sort', true );
            $attributes = apply_filters( 'bazara_trigger_sort', $attributes );
            break;

    }

    if ( empty( $attributes ) ) {
        return $defaults;
    }

    if ( $secondary_sort ) {
        $attributes = bazara_secondary_sort( $attributes, $thensort, $sortby );
    }

    $stock_status = array();

    $count = count( $attributes );
    for( $i = 0; $i < $count; $i++ ) {
        $_prod = wc_get_product( $attributes[$i]['id'] );

        $stock_limit = 0;
        $stock_qty = $_prod->get_stock_quantity();

        if ( !empty( $stock_limit ) ) {
            if ( $stock_qty < $stock_limit && !is_null( $stock_qty ) ) {
                $stock = 'outofstock';
            } else {
                $stock = $_prod->get_stock_status();
            }
        } else {
            $stock = $_prod->get_stock_status();
        }

        if ( $stock_qty <= 0) {
            $stock_status[$i] = 'outofstock';
        } else {
            $stock_status[$i] = 'instock';
        }
    }

    if ( count( array_unique( $stock_status ) ) > 1 && count( array_unique( $stock_status ) ) < count( $attributes ) ) {
        foreach( $stock_status as $key => $value ) {
            if ( $value == 'outofstock' ) {
                unset( $attributes[$key] );
            }
        }
    }

    $attributes = array_values($attributes);

    $_prod = !empty( $attributes[0]['id'] ) ? wc_get_product( $attributes[0]['id'] ) : false;

    if ( empty( $_prod ) ) {
        return apply_filters( 'bazara_attributes_return', $defaults );
    }

    $attr = bazara_populate_empty_attributes( $_prod->get_attributes(), $_prod );

    $defaults = array();

    foreach( $attr as $key => $value ) {
        if ( !empty( $value ) ) {
            $defaults[$key] = $value;
        }
    }
    
   
    return $defaults;
}
function bazara_hide_out_of_stock_option( $option ){
    return 'yes';
    }
function bazara_secondary_sort( $attributes, $sortby, $origial_sort ) {

    $attribute_split = array();
    foreach( $attributes as $akey => $avalue ) {
        $attribute_split[$avalue['price']][] = $avalue;
    }

    foreach( $attribute_split as $skey => $split ) {
        switch ( apply_filters( 'bazara_secondary_sort_switch', $sortby ) ) {

            //Sort using the Secondary filter - Currently defaults to Position, so don't change anything if set to Position
            case 'then_sales':
                $split = bazara_multidimensional_sort( $split, 'sales' );
                break;

            case 'then_id':
                $split = bazara_multidimensional_sort( $split, 'id' );
                break;

            case 'then_stock' :
                $split = bazara_multidimensional_sort( $split, 'stock' );
                break;

            default:
                $split = apply_filters( 'bazara_trigger_sort', $split );
                break;

        }

        $attribute_split[$skey] = $split;
    }

    $attributes = bazara_array_flatten( $attribute_split );

    return apply_filters( 'bazara_secondary_sort_filter', $attributes );

}

function bazara_array_flatten($array) {
    if (!is_array($array)) {
        return FALSE;
    }
    $result = array();
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = array_merge($result, $value);
        }
        else {
            $result[$key] = $value;
        }
    }
    return $result;
}

function bazara_populate_empty_attributes( $attributes, $product ) {

    foreach( $attributes as $a_key => $a_value ) {
        if ( empty( $a_value ) ) {
            $parent_id = wc_get_product( $product->get_id() )->get_parent_id();
            if ( strpos( $a_key, 'pa_' ) !== false ) {
                $attrs = wc_get_product_terms( $parent_id, $a_key, array( 'fields' => 'names' ) );
            } else {
                $attrs = bazara_get_product_attributes( $parent_id, $a_key );
            }
            $attr = array_shift( $attrs );

            if ( !empty( $attr ) ) {
                $attributes[$a_key] = strtolower( str_replace( ' ', '_', $attr ) );
            }
        }
    }

    return apply_filters( 'bazara_empty_attribute_return', $attributes );
}

function bazara_get_product_attributes( $product_id, $a_key ) {
    $attributes = get_post_meta( $product_id, '_product_attributes', true )[$a_key];

    $attribute_array = array();
    if ( !empty( $attributes['value'] ) ) {
        $attribute_array = explode( '|', str_replace( ' | ', '|', $attributes['value'] ) );
    }

    return $attribute_array;
}

function bazara_multidimensional_sort( $array, $check, $attribute = '' ) {

    if ( $check == 'price-low' ) {
        usort( $array, 'bazara_sortByPrice' );
    } else if ( $check == 'price-high' ) {
        usort( $array, 'bazara_sortByPriceHigh' );
    } else if ( $check == 'position' ) {
        usort( $array, 'bazara_sortByPosition' );
    } else if ( $check == 'menu_order' ) {
//        usort( $array, 'bazara_sortByAttribute', $attribute );
        usort($array, function($a, $b) use ($attribute) {
            return $a['menu_order'][ $attribute ] - $b['menu_order'][ $attribute ];
        });
    } else {
        usort( $array, 'bazara_sortByID' );
    }

    return apply_filters( 'bazara_sort_filter', $array );

}

function bazara_sortByPrice($a, $b) {
    return $a['price'] - $b['price'];
}

function bazara_sortByPriceHigh($a, $b) {
    return $b['price'] - $a['price'];
}

function bazara_sortByPosition($a, $b) {
    return $a['position'] - $b['position'];
}

function bazara_sortByAttribute($a, $b, $attribute) {
    return $a['id'][ $attribute ] - $b['id'][ $attribute ];
}

function bazara_sortByID($a, $b) {
    return $a['id'] - $b['id'];
}

function bazara_get_attribute_menu_order( $child, $parent ) {
    $attributes = $child->get_attributes();
    $p_attributes = $parent->get_attributes();

    $p_variations = $parent->get_available_variations();

    foreach( $p_attributes as $p_attribute ) {
        if ( $p_attribute ) {

        }
    }

    $_primary = false;
    foreach( $attributes as $key => $value ) {
        //Check for Primary Attribute. If not set, or multiple set, use first available Attribute.
        $primary = get_post_meta( $parent->get_id(), 'attribute_' . $key . '_primary', true );
        if ( $primary ) {
            $_primary = $key;
            break;
        }
    }

    if ( !$_primary ) {
        $_primary = array_key_first( $attributes );
    }

    $_atts = explode( ', ', $parent->get_attribute( $_primary ) );

    $order = array_search( $attributes[$_primary], $_atts );

    return $order;
}
