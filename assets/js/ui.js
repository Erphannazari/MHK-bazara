var bazaraSyncSupportApproved = false;

jQuery(document).ready(function () {
    init();
    // setInterval(function() {
    //     getSyncPercent();
    //     }, 500); // 60 * 1000 milsec
    
    jQuery('select').select2();
    
    jQuery('.btn-check').change(function(){
        var checked = jQuery(this).is(":checked");
        if (checked){
            jQuery(this).closest('table').find('label').removeClass('btnchecked');
            jQuery(this).next().addClass('btnchecked');
        }
        


    });
    jQuery(document).on("click", ".bazara_popdismiss,.bazara_popmessage_contents", function () {
        jQuery('.bazara_popmessage').remove();

    });
    
    jQuery('input:radio:not(.btn-check)').change(function(){
        var dobjs = jQuery(this).data('disable-objects');
        var eobjs = jQuery(this).data('enable-objects');
        if (undefined !== dobjs)
        {
            for (var obj in dobjs)
            {

                var element = jQuery('#'+ dobjs[obj]);
               
                element.attr('disabled','');
                 

            }
        }
        if (undefined !== eobjs)
        {
            for (var obj in eobjs)
            {
                var element = jQuery('#'+ eobjs[obj]);
               
                element.removeAttr('disabled');
                 

            }
        }
    });
    jQuery('#bazara_new_person_toggle').change(function(){
        
        var rdoALlCustomer = jQuery('#radioAllCustomer').is(':checked');
        var radioPublicCustomer = jQuery('#radioPublicCustomer').is(':checked');
        if (jQuery(this).is(':checked') && rdoALlCustomer){
        jQuery('#selectPerson').attr('disabled','');
        jQuery('#selectGroup').attr('disabled','');
        }else   if (jQuery(this).is(':checked') && radioPublicCustomer){
        jQuery('#selectPerson').removeAttr('disabled');
        jQuery('#selectGroup').removeAttr('disabled');

        }else{
            jQuery('#selectPerson').attr('disabled','');
            jQuery('#selectGroup').attr('disabled','');

        }


     });
    
     var dig_sort_fields = jQuery(".dig-reg-fields").find('tbody');
     var dig_reg_fields_not_ordered = jQuery(".dig-reg-fields-not-ordered").find('tbody');
     dig_reg_fields_not_ordered.find('.icon-drag').css('display','none');
     dig_sort_fields.find('input:checkbox').prop('checked',true);

     jQuery('.chk_store').change(function(){
        var checked = jQuery(this).is(":checked");
        var closestTr = jQuery(this).closest('tr');
        if (checked){
            dig_sort_fields.append(closestTr);
            dig_sort_fields.find('.icon-drag').css('display','block');
        }
        else{
            dig_reg_fields_not_ordered.append(closestTr);
            dig_reg_fields_not_ordered.find('.icon-drag').css('display','none');

        }
        if (dig_sort_fields.length) {
            var sortOrder = dig_sort_fields.sortable('toArray').toString();
            dig_sortorder.val(sortOrder);
    }

     });
    jQuery('.bazara_wp_products_inte').change(function(){
        var checked = jQuery(this).is(":checked");
        var objs = jQuery(this).data('validation-objects');
        if (undefined !== objs)
        {
            for (var obj in objs)
            {
                // alert(obj);
                var element = jQuery('#'+ objs[obj]);
                if (!checked){
                    element.attr('disabled','');
                    if (element.is('input:checkbox')){
                        element.prop('checked', false); 
                        element.trigger('change');
                    }
                    if (element.is('input:text') )
                    element.val('');

                    if (element.is('table')){
                        element.find("input,button,textarea,select").attr("disabled", "disabled");
                        dig_sort_fields.sortable('disable'); 

                    }


                }
                else{
                    element.removeAttr('disabled');
                    if (element.is('input:checkbox')){
                        element.prop('checked', false); 
                        element.trigger('change');
                    }
                    if (element.is('table')){
                        element.find("input,button,textarea,select").removeAttr("disabled");
                        dig_sort_fields.sortable('enable'); 

                    }
                    
                }

            }
        }

    });

    if (dig_sort_fields.length) {
        var dig_sortorder = jQuery("#dig_sortorder");

    var sortorder = dig_sortorder.val().split(',');

    dig_sort_fields.find('tr').sort(function (a, b) {
        var ap = jQuery.inArray(a.id, sortorder);
        var bp = jQuery.inArray(b.id, sortorder);
        return (ap < bp) ? -1 : (ap > bp) ? 1 : 0;


    }).appendTo(dig_sort_fields);


    dig_sort_fields.sortable({
        update: function (event, ui) {
            var sortOrder = jQuery(this).sortable('toArray').toString();
            dig_sortorder.val(sortOrder);

            allowUpdateSettings();
        }
    });
}


    jQuery('#for_variation_date').change(function(){
        var checked = jQuery(this).is(":checked");
        if (checked)
        jQuery('#bazara_VariationDateCondition').attr('disabled','');
        else
        jQuery('#bazara_VariationDateCondition').removeAttr('disabled');

    });
    
    
    jQuery("#bazara_save_form_setting").click(function () {
        check_login_sync()
        // save_form_setting();
        // jQuery('input:radio').trigger('change');

    });

    jQuery('#bazara_entity_sync_toggle').change(function () {
        if (jQuery(this).is(':checked') && jQuery('#bazara_product_batch_sync_toggle').is(':checked')) {
            jQuery(this).prop('checked', false);
            showBazaraErrorMessage('فقط یکی از ماژول‌های دریافت اطلاعات یا سینک محصولات می‌تواند فعال باشد.');
        }

        jQuery('#bazara_options_sync_min, #bazara_options_sync_max')
            .prop('disabled', !jQuery(this).is(':checked'));
    });

    jQuery('#bazara_product_batch_sync_toggle').change(function () {
        if (!jQuery(this).is(':checked')) {
            jQuery('#bazara_options_product_sync_min, #bazara_options_product_sync_max').prop('disabled', true);
            return;
        }

        if (jQuery('#bazara_entity_sync_toggle').is(':checked')) {
            jQuery(this).prop('checked', false);
            showBazaraErrorMessage('برای فعال‌کردن سینک محصولات، ابتدا ماژول دریافت اطلاعات را خاموش کنید.');
        } else if (bazaraEntitySourceSettingsChanged() || jQuery(this).data('entities-completed') != 1) {
            jQuery(this).prop('checked', false);
            showBazaraErrorMessage('ابتدا باید دریافت تمام Entityها با موفقیت تکمیل شود.');
        }

        jQuery('#bazara_options_product_sync_min, #bazara_options_product_sync_max')
            .prop('disabled', !jQuery(this).is(':checked'));
    });
    jQuery("#bazara_sale_price_toggle").change(function () {
        var radioPercent = jQuery("#radioPercentDiscount").is(":checked");
        var radioPrice = jQuery("#radioPriceDiscount").is(":checked");
        if (jQuery(this).is(":checked") && !radioPercent && !radioPrice){
            jQuery("#radioPercentDiscount").prop('checked', true); 
            jQuery("#radioPercentDiscount").trigger("change");
        }

    });

    
      
});
function draggable_body(){
    
}

function bazaraBooleanValue(value, defaultValue) {
    if (typeof value === 'undefined' || value === null) {
        return defaultValue;
    }

    return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
}

function bazaraCurrentSyncModuleSettings() {
    return {
        entity_sync_enabled: jQuery('#bazara_entity_sync_toggle').is(':checked'),
        sync_min: parseInt(jQuery('#bazara_options_sync_min').val(), 10),
        sync_max: parseInt(jQuery('#bazara_options_sync_max').val(), 10),
        product_sync_enabled: jQuery('#bazara_product_batch_sync_toggle').is(':checked'),
        product_sync_min: parseInt(jQuery('#bazara_options_product_sync_min').val(), 10),
        product_sync_max: parseInt(jQuery('#bazara_options_product_sync_max').val(), 10)
    };
}

function bazaraSavedSyncModuleSettings() {
    var saved = params.visitor_setting || {};

    return {
        entity_sync_enabled: bazaraBooleanValue(saved.entity_sync_enabled, true),
        sync_min: parseInt(typeof saved.sync_min === 'undefined' ? 0 : saved.sync_min, 10),
        sync_max: parseInt(typeof saved.sync_max === 'undefined' ? 100000 : saved.sync_max, 10),
        product_sync_enabled: bazaraBooleanValue(saved.product_sync_enabled, false),
        product_sync_min: parseInt(typeof saved.product_sync_min === 'undefined' ? 0 : saved.product_sync_min, 10),
        product_sync_max: parseInt(typeof saved.product_sync_max === 'undefined' ? 10000 : saved.product_sync_max, 10)
    };
}

function bazaraEntitySourceSettingsChanged() {
    var current = bazaraCurrentSyncModuleSettings();
    var saved = bazaraSavedSyncModuleSettings();

    return current.sync_min !== saved.sync_min
        || current.sync_max !== saved.sync_max
        || (!saved.entity_sync_enabled && current.entity_sync_enabled);
}

function bazaraSyncModuleSettingsChanged() {
    var current = bazaraCurrentSyncModuleSettings();
    var saved = bazaraSavedSyncModuleSettings();

    return Object.keys(current).some(function (key) {
        return current[key] !== saved[key];
    });
}

function bazaraValidateSyncModuleSettings() {
    var settings = bazaraCurrentSyncModuleSettings();

    if (settings.entity_sync_enabled && settings.product_sync_enabled) {
        return 'فقط یکی از ماژول‌های دریافت اطلاعات یا سینک محصولات می‌تواند فعال باشد.';
    }

    if (isNaN(settings.sync_min) || isNaN(settings.sync_max) || settings.sync_min < 0 || settings.sync_max <= settings.sync_min) {
        return 'بازه دریافت اطلاعات معتبر نیست.';
    }

    if (
        isNaN(settings.product_sync_min)
        || isNaN(settings.product_sync_max)
        || settings.product_sync_min < 0
        || settings.product_sync_max < 1
        || settings.product_sync_max > 10000
    ) {
        return 'بازه سینک محصولات معتبر نیست؛ تعداد هر مرحله باید بین ۱ تا ۱۰۰۰۰ باشد.';
    }

    if (
        settings.product_sync_enabled
        && (
            bazaraEntitySourceSettingsChanged()
            || jQuery('#bazara_product_batch_sync_toggle').data('entities-completed') != 1
        )
    ) {
        return 'ابتدا باید دریافت تمام Entityها با موفقیت تکمیل شود.';
    }

    return '';
}

function bazaraUpdateSyncModuleSnapshot() {
    var entitySettingsChanged = bazaraEntitySourceSettingsChanged();
    var current = bazaraCurrentSyncModuleSettings();

    params.visitor_setting = params.visitor_setting || {};
    Object.keys(current).forEach(function (key) {
        params.visitor_setting[key] = current[key];
    });

    if (entitySettingsChanged) {
        params.visitor_setting.entity_sync_completed = false;
        jQuery('#bazara_product_batch_sync_toggle').data('entities-completed', 0);
        jQuery('#bazara_entity_sync_status').data('completed', 0).text('وضعیت دریافت: تکمیل‌نشده');
    }
}

function init(){

    var objectChecked = jQuery("#bazara_sale_price_toggle").is(":checked");
    var radioPercent = jQuery("#radioPercentDiscount").is(":checked");
    var radioPrice = jQuery("#radioPriceDiscount").is(":checked");
    if (objectChecked && !radioPercent && !radioPrice){
        jQuery("#radioPercentDiscount").prop('checked', true); 
        jQuery("#radioPercentDiscount").trigger('change');
    }

    jQuery('#bazara_setting_update #bazara-library-sync-button, #bazara_setting_update #btn_save_setting')
        .off('click.bazaraSyncOptions')
        .on('click.bazaraSyncOptions', function () {
        jQuery(this).removeClass('success').addClass('loading');

        jQuery('#bazara_options_refresh_interval').css('border-color','#8c8f94');

        var obj = jQuery(this);
        var validationMessage = bazaraValidateSyncModuleSettings();

        if (validationMessage) {
            showBazaraErrorMessage(validationMessage);
            obj.removeClass('loading').addClass('success');
            return false;
        }

        if (bazaraSyncModuleSettingsChanged() && !bazaraSyncSupportApproved) {
            obj.removeClass('loading').addClass('success');
            jQuery('#modalCallbackForm').val('syncOptionsForm');
            jQuery('#bazaraModalSupportLogin').fadeIn();
            return false;
        }

        bazaraSyncSupportApproved = false;

        if (jQuery('#bazara_intver_toggle').is(":checked") && (jQuery('#bazara_options_refresh_interval').val() == "" || jQuery('#bazara_options_refresh_interval').val() == "0"))
        {
            showBazaraErrorMessage("زمان همگام سازی خودکار را وارد نمایید");
            jQuery('#bazara_options_refresh_interval').css('border-color','red');
            obj.removeClass('loading').addClass('success');

        }else{
            jQuery.ajax({
                url : params.ajax_url,
                type : 'post',
                data : {
                    action:"bazara_save_visitor_setting",
                    security:jQuery('#security').val(),
                    username:jQuery('#bazara_options_username').val(),
                    password:jQuery('#bazara_options_password').val(),
                    refresh_interval:jQuery('#bazara_options_refresh_interval').val(),
                    active_auto_sync:jQuery('#bazara_intver_toggle').is(":checked"),
                    entity_sync_enabled:jQuery('#bazara_entity_sync_toggle').is(":checked"),
                    sync_min:jQuery('#bazara_options_sync_min').val(),
                    sync_max:jQuery('#bazara_options_sync_max').val(),
                    product_sync_enabled:jQuery('#bazara_product_batch_sync_toggle').is(":checked"),
                    product_sync_min:jQuery('#bazara_options_product_sync_min').val(),
                    product_sync_max:jQuery('#bazara_options_product_sync_max').val(),
    
                },
                success : function( response ) {
                    if (response.status === 1)
                    {
                        obj.removeClass('loading').addClass('success');
                        bazaraUpdateSyncModuleSnapshot();
                        showBazaraSuccessMessage("تنظیمات ذخیره شد");
                        fill_login_box(response);
                    }else if (response.status === 2){
                        bazaraUpdateSyncModuleSnapshot();
                        params.visitor_setting.entity_sync_completed = false;
                        jQuery('#bazara_product_batch_sync_toggle').data('entities-completed', 0);
                        jQuery('#bazara_entity_sync_status').data('completed', 0).text('وضعیت دریافت: تکمیل‌نشده');
                        if(!confirm(response.result))
                        fill_login_box(response);
                        else
                        jQuery.ajax({
                            url : params.ajax_url,
                            type : 'post',
                            data : {
                                action : 'bazara_change_visitor_ajax',
                                security : params.ajax_nonce
                            },
                            success : function( response ) {
                                obj.removeClass('loading').addClass('success');
                                params.visitor_setting.entity_sync_completed = false;
                                jQuery('#bazara_product_batch_sync_toggle').data('entities-completed', 0);
                                jQuery('#bazara_entity_sync_status').data('completed', 0).text('وضعیت دریافت: تکمیل‌نشده');
                                fill_login_box(response);
                                showBazaraSuccessMessage("تنظیمات ذخیره شد");
                            }
                        }).fail(function(e){
                            obj.removeClass('loading').addClass('success');
                            showBazaraErrorMessage('خطا در تغییر اطلاعات اتصال');
                        });
                    }else {
                        obj.removeClass('loading').addClass('success');
                        showBazaraErrorMessage(response.result || 'خطا در ذخیره تنظیمات');
                    }
    
                }
            }).fail(function(jqXHR){
                obj.removeClass('loading').addClass('success');
                calling = false;
                jQuery(".loader").hide();
                var detail = (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.result) ? jqXHR.responseJSON.result : ((jqXHR && jqXHR.responseText) ? jqXHR.responseText.substring(0, 200) : '');
                showBazaraErrorMessage(detail || 'خطا در ذخیره تنظیمات');
                console.log('bazara_save_visitor_setting fail', jqXHR);
            });
        }
        

        return false;
    });

    var tab = getUrlParameter("tab");
    if (undefined !== tab)
    {
        jQuery("#" + tab).trigger("click");
    }
   
}
function fill_login_box(response)
{
    jQuery(".visitor-section").html('<h1>اطلاعات کاربر</h1>'+
    '<table style="width:25%">'+
    '<tbody>'+
        '<tr>'+
        '<td data-id="site_package_number" style="font-size: 26px;padding-bottom:10px"> '+ response.data.DatabaseId +
        '<img src="'+params.plugin_dir_url+'assets/img/Verified.png" style="width: 20px;"></td>'+
        '<td style="text-align: left;"><a onclick="change_visitor_login();">تغییر</a></td>'+
        '</tr>'+
    '</tbody>'+
    '</table>'+
    '<table style="width:25%;background-color: #ebebeb;line-height: 2.5;border: 2px solid #cecbcb;">'+
    '<tbody>'+
        '<tr>'+
        '<td data-id="site_name" style="color: #686262;"> '+ response.data.UserTitle +
    '</td>'+
    '</tr>'+
    '<tr>'+
    '<td data-id="site_desc" style="color: #686262;"> توضیح سایت'+
    '</td>'+
    '</tr><tr style="border-top: 1px solid #000;">'+
    '<td data-id="site_expiredate" style="border-top: 0.3px solid #000;color: #a99e9e;border-color: #a99e9e;">'+
     'تاریخ اعتبار :'+
    '</td>'+
        '</tr>'+
    '</tbody>'+
    '</table>'
    );
}

function modalClose(){
    jQuery('#bazaraModalSupportLogin').fadeOut();
    jQuery('#bazaraModalSeoConfirm').fadeOut();
}

function syncSubmitLogin(){
    jQuery('#bazaraModalSupportLogin').fadeOut();
    var syncPasswordInput = jQuery('#syncPasswordInput').val();
    var modalCallbackForm = jQuery('#modalCallbackForm').val();

    let res = jQuery.ajax({
        url: params.ajax_url,
        type: 'post',
        data: "syncPasswordInput=" + encodeURIComponent(syncPasswordInput)
            + "&callbackForm=" + encodeURIComponent(modalCallbackForm)
            + "&security=" + params.ajax_nonce
            + "&action=bazara_check_support_login",
        success: function (response) {
            if (bazaraBooleanValue(response.access, false)) {
                if (modalCallbackForm == 'syncForm') {
                    save_form_setting()
                }
                if (modalCallbackForm == 'loginForm') {
                    change_visitor_login_form()
                }
                if (modalCallbackForm == 'syncOptionsForm') {
                    bazaraSyncSupportApproved = true;
                    jQuery('#syncPasswordInput').val('');
                    jQuery('#bazara-library-sync-button').trigger('click');
                }
            }
            else{
                showBazaraErrorMessage(response.message);
                jQuery('#bazaraModalSupportLogin').fadeIn();
            }
        }
    }).fail(function (jqXHR) {
        var detail = (jqXHR && jqXHR.responseText) ? jqXHR.responseText.substring(0, 200) : '';
        var msg = 'خطا در ورود پشتیبان' + (detail ? ': ' + detail : '');
        showBazaraErrorMessage(msg);
        console.log('bazara_check_support_login fail', jqXHR);
    });
}

function check_user_validate(){
    jQuery('#bazaraModalSupportLogin').fadeIn();
}

function check_login_sync(){
    jQuery.ajax({
        url: params.ajax_url,
        type: 'post',
        data: jQuery('#bazara_form_setting').serialize() + "&security=" + params.ajax_nonce + "&action=bazara_check_user_access",
        success: function (response) {
            if(response.seoMessage) {
                jQuery('#bazaraModalSeoConfirm').fadeIn();
                jQuery('#bazaraModalSeoConfirmMessage').html( response.seoMessage)
                jQuery('#bazaraModalSeoConfirmDataSync').val(response.checkShouldSync)
                jQuery('#bazaraModalSeoConfirmDataLogin').val(response.checkShouldLogin)
            }
            else if(response.checkShouldSync && response.checkShouldLogin){
                jQuery('#bazaraModalSupportLogin').fadeIn();
                jQuery("#modalCallbackForm").val('syncForm')
            }
            else if(response.checkShouldSync){
                save_form_setting();
            }
            else{
                showBazaraErrorMessage('تغییری لحاظ نشد');
            }
        }
    }).fail(function (e) {
        showBazaraErrorMessage('خطا در ارسال اطلاعات');
        console.log('error');
    });
}

function bazaraModalSeoConfirmSubmit(){
    jQuery('#bazaraModalSeoConfirm').fadeOut();
    let checkShouldSync = jQuery('#bazaraModalSeoConfirmDataSync').val()
    let checkShouldLogin = jQuery('#bazaraModalSeoConfirmDataLogin').val()
    if (checkShouldSync) {
        if (checkShouldLogin) {
            jQuery('#bazaraModalSupportLogin').fadeIn();
            jQuery("#modalCallbackForm").val('syncForm')
        } else {
            save_form_setting()
        }
    } else {
        showBazaraErrorMessage("اطلاعات یکسان هستند");
    }
}

function save_form_setting() {
    jQuery(this).removeClass('success').addClass('loading');

    jQuery.ajax({
        url : params.ajax_url,
        type : 'post',
        data : jQuery('#bazara_form_setting').serialize() + "&security="+params.ajax_nonce + "&action=bazara_save_settings",
        success : function( response ) {
            jQuery(this).removeClass('loading').addClass('success');
            showBazaraSuccessMessage("تنظیمات ذخیره شد");

        }
    }).fail(function(e){
        jQuery(this).removeClass('loading').addClass('success');
        showBazaraErrorMessage("خطا در ذخیره اطلاعات");

    });
}
function change_visitor_login(){
    jQuery('#bazaraModalSupportLogin').fadeIn();
    jQuery("#modalCallbackForm").val('loginForm')
}
function change_visitor_login_form(){
    jQuery(".visitor-section").html('<script>init();</script><input type="hidden" name="security" id="security" value="'+ params.ajax_nonce +'">'+
        '<script></script><h1>اتصال به نرم افزار حسابداری محک</h1>'+
        '<h3>نام کاربری و رمز عبور خود را وارد نمایید.</h3>'+
        '<br/><br/>'+
        '<table class="form-table  " >'+
        '<tbody data-select2-id="102"><tr>'+
        '<th scope="row" valign="top" style="vertical-align: top;">'+
        '</th>'+
        '<tr class="ippanelcred " >'+
        '<th scope="row"><label for="bazara_username"> نام کاربری  </label></th>'+
        '<td><input type="text" id="bazara_options_username" name="username" class="regular-text" value="' + params.visitor_setting.username + '" autocomplete="off"  bazara-optional="0" required="required">'+
        '</td>'+
        '</tr>'+
        '<tr class="ippanelcred " >'+
        '<th scope="row"><label for="bazara_password"> رمز عبور  </label></th>'+
        '<td><input type="password" id="bazara_options_password" name="password" class="regular-text" value="' + params.visitor_setting.password + '" autocomplete="off"  bazara-optional="0" required="required">'+
        '</td>'+
        '</tr>'+
        '<tr class="ippanelcred " >'+
        '<th></th><td><div id="btn_save_setting" class="bazara_call_test_api_btn bazara-button-spinner" id="bazara-library-sync-button"><label>اتصال</label></div></td>'+
        '</tr>'+
        '</tbody></table>');
}

function showBazaraErrorMessage(message) {
    showBazaraMessage(message, 3);
}

function showBazaraNoticeMessage(message) {
    showBazaraMessage(message, 2);
}

function showBazaraSuccessMessage(message) {
    showBazaraMessage(message, 1);
}

function showBazaraMessage(message, alert_type) {

    jQuery(".bazara_error_message").remove();

    jQuery("body").append("<div class='bazara_popmessage bazara_popmessage_right bazara_error_message'><div class='bazara_popmessage_contents'><div class='bazara_firele'></div><div class='bazara_lasele'><div class='bazara_lase_snap'></div><div class='bazara_lase_message'>" + message + "</div></div><div class='bazara_popdismiss'></div></div></div>");

    var alert_class;
    if (alert_type === 1) {
        alert_class = 'bazara_success_msg';
    } else if (alert_type === 2) {
        alert_class = 'bazara_notice_msg';
    } else {
        alert_class = 'bazara_critical_msg';
    }

    jQuery(".bazara_popmessage").show().removeClass('bazara_success_msg bazara_notice_msg bazara_critical_msg').addClass(alert_class + ' bazara_popBounceInRight').delay(5000).fadeOut(400);;

}

function hideBazaraMessage() {
    jQuery(".bazara_popmessage").fadeOut('fast', function () {
        jQuery(this).remove();
    });
}
 function getSyncPercent()
{
    var remain = 0;
    jQuery.ajax({
        url : params.ajax_url,
        data : "security="+params.ajax_nonce + "&action=bazara_get_sync_percentage",
        type : 'post',
        success : function( response ) {
            console.log(response);
            remain = Math.round((response.sync /response.all) * 100) ;
            jQuery('.progress-bar span').css('width',(remain + '%'));

        }
    }).fail(function(e){
        
    });
}
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    return false;
};
