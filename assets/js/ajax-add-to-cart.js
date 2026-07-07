(function ($) {
    $(document).on('change', '#whole_sale_radio', function (e) {

        var checked = $(this).is(':checked');
        if (checked)
        {
            $('.woocommerce-variation-add-to-cart').css('display','none');
            $('.bazara_d').css('display','block');

        }else{
            $('.woocommerce-variation-add-to-cart').css('display','block');
            $('.bazara_d').css('display','none');
        }            


    });

    $(document).on('click', '.single_add_to_cart_button2', function (e) {
        e.preventDefault();
    
        var $thisbutton = $(this),
                $form = $thisbutton.closest('form.cart'),
                id = $thisbutton.val(),
                product_id = $form.find('input[name=product_id]').val() || id,
                floatQty = parseFloat($form.find('input[name=bazara_quantity]').val()) || 0.5

    
        var data = {
            action: 'bazara_woocommerce_ajax_add_to_cart',
            product_id: product_id,
            floatQty : floatQty
        };
    
        $(document.body).trigger('adding_to_cart', [$thisbutton, data]);
    
        $.ajax({
            type: 'post',
            url: wc_add_to_cart_params.ajax_url,
            data: data,
            beforeSend: function (response) {
                $thisbutton.removeClass('added').addClass('loading');
            },
            complete: function (response) {
                $thisbutton.addClass('added').removeClass('loading');
                console.log(response);
            },
            success: function (response) {
    
                if (response.error && response.product_url) {
                    // window.location = response.product_url;
                    return;
                } else {
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $thisbutton]);
                }
            },
        });
    
        return false;
    });
    })(jQuery);