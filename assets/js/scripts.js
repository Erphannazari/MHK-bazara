  jQuery(function () {

    var bazara_tab_wrapper = jQuery(".bazara-tab-wrapper");
    if (bazara_tab_wrapper.length) {
   
        var bazara_left_side = jQuery(".bazara_admin_left_side");
        jQuery(window).on('load', function () {
            update_tab_width();
        });
        jQuery(window).on('resize', function () {
            update_tab_width();
            update_tab_sticky();
            update_tb_line();

        });

        var respon_win = 822;
        var tb_top = bazara_tab_wrapper.offset().top;
        var ad_bar_height = jQuery("#wpadminbar").outerHeight(true);

        jQuery(window).on('scroll', function () {
            update_tab_sticky();
        });

        function update_tab_sticky() {
            var w_top = jQuery(window).scrollTop();
            var sb = tb_top - w_top;
            if (sb <= ad_bar_height && jQuery(window).width() >= respon_win) {
                bazara_tab_wrapper.addClass("bazara-tab-wrapper-sticky").css({'top': ad_bar_height});
            } else {
                bazara_tab_wrapper.removeClass("bazara-tab-wrapper-sticky");
            }
        }

        function update_tab_width() {
            var w = bazara_left_side.width();
            // bazara_admin_submit.css({'left': bazara_left_side.offset().left + w - 168});

        }

        jQuery(window).trigger('scroll');

    }

    $mainNav = jQuery(".bazara-tab-ul");

  
    var $el, leftPos, newWidth;

    $mainNav.append("<li id='bazara-tab-magic-line' style='display: none'></li>");
    var $magicLine = jQuery("#bazara-tab-magic-line");


    jQuery(document).on('click', '.bazara_preset_big_img', function () {

        var src = jQuery(this).attr('href');
        var p = jQuery(".bazara_big_preset_show");

        p.find('img').attr('src', '').attr('src', src);
        p.fadeIn('fast');
        return false;
    });

    setTimeout(function () {
        $magicLine.show();
        setTimeout(update_tb_line(), 2000);
    })

    function update_tb_line() {
        var bazara_active_tab = jQuery(".bazara-nav-tab-active").first();


        if (!bazara_active_tab.length) return;


        var bazara_active_tab_par_pos = bazara_active_tab.parent().position();
        $magicLine
            .width(bazara_active_tab.parent().width())
            .css({
                "left": bazara_active_tab_par_pos.left,
                "top": bazara_active_tab_par_pos.top + 35
            })
            .data("origLeft", $magicLine.position().left)
            .data("origWidth", $magicLine.width());
        if (bazara_active_tab.hasClass("bazara_ngmc") && !bazara_active_tab.hasClass("customfieldsNavTab")) {
            $magicLine.hide().css({'top': 55});
        }
    }

    jQuery(".bazara_admim_conf .updatetabview").on('click', function () {


        var c = jQuery(this).attr('tab');

        var acr = jQuery(this).attr('acr');

        var refresh = jQuery(this).attr('refresh');

        if (typeof refresh !== typeof undefined && refresh !== false) {
            location.reload();
            return true;
        }


        var tab = jQuery("." + c);

        var $this = jQuery(this);

        if (tab.hasClass('bazara_currentactive')) return false;

        if (tab.data('attach')) {
            $this = jQuery('.' + tab.data('attach'));
        }

        if (!$this.hasClass("bazara_ngmc")) {
            $magicLine.show();
            $el = $this.parent();
            leftPos = $el.position().left;
            newWidth = $el.width();
            $magicLine.stop().animate({
                left: leftPos,
                width: newWidth,
                top: $el.position().top + 35
            }, 'fast');
        } else {
            $magicLine.hide();
        }


        jQuery(".bazara_currentactive").removeClass("bazara_currentactive").hide();


        tab.fadeIn(150).addClass("bazara_currentactive");


        if (jQuery(".bazara-tab-wrapper-sticky").length)
            jQuery('html, body').animate({scrollTop: tab.offset().top - 90}, 220);


        jQuery(".bazara-nav-tab-active").removeClass("bazara-nav-tab-active");
        jQuery(this).addClass("bazara-nav-tab-active");


        updateURL("tab", c);

        return false;
    });

    function updateURL(key, val) {
        var url = window.location.href;
        var reExp = new RegExp("[\?|\&]" + key + "=[0-9a-zA-Z\_\+\-\|\.\,\;]*");

        if (reExp.test(url)) {
            // update
            var reExp = new RegExp("[\?&]" + key + "=([^&#]*)");
            var delimiter = reExp.exec(url)[0].charAt(0);
            url = url.replace(reExp, delimiter + key + "=" + val);
        } else {
            // add
            var newParam = key + "=" + val;
            if (!url.indexOf('?')) {
                url += '?';
            }

            if (url.indexOf('#') > -1) {
                var urlparts = url.split('#');
                url = urlparts[0] + "&" + newParam + (urlparts[1] ? "#" + urlparts[1] : '');
            } else {
                url += "&" + newParam;
            }
        }
        window.history.pushState(null, document.title, url);
    }

    
    jQuery(document).on("change", ".bazara_admim_conf .input-switch input", function () {
        var cls = jQuery(this).attr('name');
        var off = jQuery('.' + cls + '_off');
        if (jQuery(this).prop("checked") === true) {
            jQuery(this).parent().addClass('checked');
            off.prop("checked", false);
        } else {
            jQuery(this).parent().removeClass('checked').prop("checked", true);
            off.prop("checked", true);
        }
    });


});


