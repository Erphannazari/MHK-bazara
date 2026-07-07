<?php 
defined( 'ABSPATH' ) || exit;
$sync_bank_toggle = ($visitorOption['chkBankOrder'] );
$sync_shipping_toggle = ($visitorOption['chkShippingOrder'] );
$bank_methods_json = stripslashes($visitorOption['banksMethods']);
$shipping_methods_json = stripslashes($visitorOption['carrierMethods']);
$sync_order_id = $visitorOption['chkLastOrderID'] ;
$last_order_id = $visitorOption['order_id_greater_than'];

?> 
<script>
    jQuery(document).ready(function () {

    var bank_methods= <?= !empty($bank_methods_json) ? $bank_methods_json : '[]' ?>,shipping_methods = <?= !empty($shipping_methods_json) ? $shipping_methods_json : '[]' ?>;
    jQuery('#banksMethods').val(JSON.stringify(bank_methods));
    jQuery('#carrierMethods').val(JSON.stringify(shipping_methods));

    jQuery('.banks').change(function(){
        var checked = jQuery(this).is(":checked");
        var id = jQuery(this).data('gid');
        var selectedBank = jQuery(this).closest('td').next('td').next('td').find('select option:selected').val();
        if (checked){
            bank_methods.push({
                method: id,
                name  : selectedBank
              })
        }else{
            var index;
            
            var index;bank_methods.findIndex(function (entry, i) {if (entry.method == id) {index = i;return true;}});
                
                    
                    
                
            
            bank_methods.splice(index,1);

        }
        jQuery('#banksMethods').val(JSON.stringify(bank_methods));

    });

    jQuery('.shippings').change(function(){
        var checked = jQuery(this).is(":checked");
        var id = jQuery(this).data('gid');
        var selectedShipping = jQuery(this).closest('td').next('td').next('td').find('select option:selected').val();
        if (checked){
            shipping_methods.push({
                method: id,
                name  : selectedShipping
              })
        }else{
            var index;shipping_methods.findIndex(function (entry, i) {if (entry.method == id) {index = i;return true;}});

            shipping_methods.splice(index,1);

        }
        jQuery('#carrierMethods').val(JSON.stringify(shipping_methods));

    });
    jQuery('.bankClass').change(function(){
            var id = jQuery(this).data('select-id');            
            var index;
            bank_methods.findIndex(
                function (entry, i) {
                    if (entry.method == id) 
                    {index = i;return true;}
                });
                bank_methods[index].name = jQuery(this).find( "option:selected" ).val();

        
        jQuery('#banksMethods').val(JSON.stringify(bank_methods));

    });
    jQuery('.shippingClass').change(function(){
            var id = jQuery(this).data('select-id');            
            var index;
            shipping_methods.findIndex(
                function (entry, i) {
                    if (entry.method == id) 
                    {index = i;return true;}
                });
                shipping_methods[index].name = jQuery(this).find( "option:selected" ).val();

        
        jQuery('#carrierMethods').val(JSON.stringify(shipping_methods));

    });
    });
    
</script>
<div class="subform">
<div class="order-section" data-select2-id="104">
                                        <table class="form-table  " data-select2-id="103">
                                            
                                            <tbody data-select2-id="102"><tr data-select2-id="111">
                                            <th scope="row" valign="top" style="vertical-align: top;">
    
                                            </th>
                                                                            </tr><tr>
                                                    <td width="5%">
                                                        
                                                        <div class="input-switch checked">
                                                <input type="checkbox" class="bazara_wp_products_inte" name="bazara_bank_order_toggle" data-validation-objects='[<?= ('"'. implode('","',get_bank_methods()).'"') ?>]' id="bazara_bank_order_toggle"  <?php echo $sync_bank_toggle  ? 'checked' : ''?>>
                                                <label for="bazara_bank_order_toggle"></label>
                                                <span class="status_text yes"></span>
                                                <span class="status_text no"></span>
                                            </div>
                                            </td>
                                                    <td>
                                                
    
                                                    <div class="bazara-div_for_caption">
    
                                            <label for="bazara_bank_order_toggle" class="top-10">تعیین حساب های بانکی متناظر با درگاه های پرداخت سایت</label>
                                            <span class="bazara-caption">در صورت عدم تعیین حساب های متناظر تمامی پرداخت ها در حساب بانکی پیش فرض تعیین شده در نرم افزار حسابداری اعمال خواهد شد.</span>
    
                                            </div>
                                            </td>
                                            <td><input type="hidden" id="banksMethods" name="banksMethods" /></td>
                                                </tr>
    
                                            </tbody></table>

                                            <br/>
                                            <div class="bazara-sub-div">
                                            <table class="form-table  " data-select2-id="103">
                                            <tbody data-select2-id="102">
                                                <tr data-select2-id="111">
                                                <th scope="row" valign="top" style="vertical-align: top;"></th>
                                                </tr>
                                                
                                                    <?php
                                                    $banks = get_banks();
                                                    $bank_methods_json = !empty($bank_methods_json) ? json_decode($bank_methods_json,true) : '';
                                                    $gateways        = WC()->payment_gateways->payment_gateways();
                                                    $b = 0;
                                                    $selectedbank  = false;
                                                    if (!empty($gateways)){
                                                    foreach ( $gateways as $gid => $gateway ) {
                                                    if ( isset( $gateway->enabled ) && 'yes' === $gateway->enabled ) {
                                                        if ($bank_methods_json)
                                                        $selectedbank = array_search($gid,array_column($bank_methods_json,'method'));
                                                        
                                                        ?>
                                                    <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" <?= !$sync_bank_toggle ? 'disabled' : '' ?> class="bazara_wp_products_inte banks" data-id="<?= $b ?>" <?=  ( $selectedbank >= 0 && (!empty($bank_methods_json[$selectedbank]['method']) ? $bank_methods_json[$selectedbank]['method'] : '') == $gid ? 'checked' : '') ?>  data-validation-objects='["selectBank_<?php echo $gid ?>"]' data-gid="<?= $gid ?>" name="bazara_new_bank_<?php echo $gid ?>_toggle" id="bazara_new_bank_<?php echo $gid ?>_toggle">
                                                    <label for="bazara_new_bank_<?= $gid ?>_toggle" class="small"></label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                </td>
                                                <td width="15%"><label for="bazara_new_bank_<?= $gid ?>_toggle" class="small"><?= $gateway->title ?></label></td>

                                                <td>
                                                  
                                                    <select <?= empty($bank_methods_json) || $bank_methods_json[$selectedbank]['method'] != $gid ? 'disabled' : '' ?> data-select-id="<?= $gid ?>"  name="banks" class="bankClass" id="selectBank_<?php echo $gid ?>">
                                                    
                                                    <?php 
                                                    foreach($banks as $bank)
                                                    {
                                                       
                                                        ?>
                                                    <option value="<?php echo $bank['BankId'] ?>" <?=  ($selectedbank >= 0 && (!empty($bank_methods_json[$selectedbank]['name']) ? $bank_methods_json[$selectedbank]['name'] : '') == $bank['BankId'] ? 'selected' : '') ?>>
                                                    <?php echo $bank['Name']  ?>  
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select>
                                                </td></tr><?php
                                                        }
                                                    }
                                                    $gid = "Digikala";
                                                    if (!empty($bank_methods_json))
                                                    $selectedbank = array_search($gid,array_column($bank_methods_json,'method'));

                                                    ?>
                                                
                                                <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" <?= !$sync_bank_toggle ? 'disabled' : '' ?> class="bazara_wp_products_inte banks" data-id="<?= $b ?>" <?=  ( $selectedbank >= 0 && (!empty($bank_methods_json[$selectedbank]['method']) ? $bank_methods_json[$selectedbank]['method'] : '') == $gid ? 'checked' : '') ?>  data-validation-objects='["selectBank_<?php echo $gid ?>"]' data-gid="<?= $gid ?>" name="bazara_new_bank_<?php echo $gid ?>_toggle" id="bazara_new_bank_<?php echo $gid ?>_toggle">
                                                    <label for="bazara_new_bank_<?= $gid ?>_toggle" class="small"></label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                </td>
                                                <td width="15%"><label for="bazara_new_bank_<?= $gid ?>_toggle" class="small">دیجیکالا</label></td>

                                                <td>
                                                  
                                                    <select <?= empty($bank_methods_json) || $bank_methods_json[$selectedbank]['method'] != $gid ? 'disabled' : '' ?> data-select-id="<?= $gid ?>"  name="banks" class="bankClass" id="selectBank_<?php echo $gid ?>">
                                                    
                                                    <?php 
                                                    foreach($banks as $bank)
                                                    {
                                                       
                                                        ?>
                                                    <option value="<?php echo $bank['BankId'] ?>" <?=  ($selectedbank >= 0 && (!empty($bank_methods_json[$selectedbank]['name']) ? $bank_methods_json[$selectedbank]['name'] : '') == $bank['BankId'] ? 'selected' : '') ?>>
                                                    <?php echo $bank['Name']  ?>  
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select>
                                                </td></tr>
                                            </tbody>
                                            </table>
                                        </div>
                                       <?php } ?>
                                        </div>
</div>

<div class="subform">
                                        <table class="form-table  " data-select2-id="103">
                                            
                                            <tbody data-select2-id="102"><tr data-select2-id="111">
                                            <th scope="row" valign="top" style="vertical-align: top;">
    
                                            </th>
                                                                            </tr><tr>
                                                    <td width="5%">
                                                        
                                                        <div class="input-switch checked">
                                                <input type="checkbox" class="bazara_wp_products_inte"  name="bazara_shipping_order_toggle" data-validation-objects='[<?= ('"'. implode('","',get_shipping_method()).'"') ?>]' id="bazara_shipping_order_toggle" <?php echo $sync_shipping_toggle  ? 'checked' : ''?>>
                                                <label for="bazara_shipping_order_toggle"></label>
                                                <span class="status_text yes"></span>
                                                <span class="status_text no"></span>
                                            </div>
                                            </td>
                                                    <td>
                                                
    
                                                    <div class="bazara-div_for_caption">
    
                                                    <label for="bazara_shipping_order_toggle" class="top-10">ارسال اطلاعات حمل و نقل سفارشات به نرم افزار حسابداری</label>
                                            <span class="bazara-caption">حساب شخص متناظر با هر یک از شیوه های حمل و نقل را جهت ثبت در نرم افزار حسابداری تعیین نمایید.در صورت عدم تعیین حساب شخص اطلاعات حمل و نقل سفارش به نرم افزار حسابداری منتقل نخواهد شد.</span>
    
                                            </div>
                                            </td>
                                            <td><input type="hidden" name="carrierMethods" id="carrierMethods" /></td>
                                                </tr>
    
                                            </tbody></table>

                                            <br/>
                                            <div class="bazara-sub-div">
                                            <table class="form-table  " data-select2-id="103">
                                            <tbody data-select2-id="102">
                                                <tr data-select2-id="111">
                                                <th scope="row" valign="top" style="vertical-align: top;"></th>
                                                </tr>
                                                
                                                    <?php
                                                    $shipping_methods = (prefix_get_available_shipping_methods());
                                                    $shipping_methods_json = !empty($shipping_methods_json) ? json_decode($shipping_methods_json,true) : '';
                                                    foreach ( $shipping_methods as $key=>$value ) {
                                                    $selectedShipping = -1;
                                                    if ( !empty($shipping_methods_json) ) {
                                                        $foundIndex = array_search($key, array_column($shipping_methods_json,'method'));
                                                        $selectedShipping = ($foundIndex === false) ? -1 : $foundIndex;
                                                    }
                                                     ?>
                                                    <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" <?= !$sync_shipping_toggle ? 'disabled' : '' ?> class="bazara_wp_products_inte shippings" data-gid="<?= $key ?>" <?= ($selectedShipping >= 0 && !empty($shipping_methods_json[$selectedShipping]['method']) && $shipping_methods_json[$selectedShipping]['method'] == $key) ? 'checked' : '' ?> data-validation-objects='["shippingPerson_<?= $key ?>"]' name="bazara_new_bank_<?php echo $key ?>_toggle" id="bazara_new_ship_<?php echo $key ?>_toggle">
                                                    <label for="bazara_new_ship_<?= $key ?>_toggle" class="small"></label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="bazara_new_ship_<?= $key ?>_toggle" ><?= $value ?></label></td>

                                                    <td>
                                                   <?php if (!empty($shipping_methods_json)) {?>
                                                    <select <?= ($selectedShipping >= 0 && !empty($shipping_methods_json[$selectedShipping]['method']) && $shipping_methods_json[$selectedShipping]['method'] == $key) ? '' : 'disabled' ?> class="shippingClass" data-select-id="<?= $key ?>"  name="person" id="shippingPerson_<?= $key ?>">
                                                    <?php 
                                                foreach($persons as $person)
                                                {?>
                                                    <option value="<?php echo $person['PersonId'] ?>"  <?= ($selectedShipping >= 0 && !empty($shipping_methods_json[$selectedShipping]['name']) && $shipping_methods_json[$selectedShipping]['name'] == $person['PersonId']) ? 'selected' : '' ?>>
                                                    <?php echo $person['FirstName'] . ' ' . $person['LastName'] ?>  
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select>
                                                </td></tr><?php
                                                        }
                                                    }
                                                    ?>
                                                
                                                
                                            </tbody>
                                            </table>
                                        </div>
                                      
                                        </div>
                                        <div class="subform">
  <table class="form-table  " data-select2-id="103">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      <tr>
        <td width="5%">
          <div class="input-switch checked">
            <input type="checkbox" class="bazara_wp_products_inte" data-validation-objects='["order_id_greater_than"]'  name="bazara_last_order_id" id="bazara_last_order_id" <?= $sync_order_id ? "checked" : ""?>>
            <label for="bazara_last_order_id"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <label for="bazara_last_order_id" class="top-10">ارسال سفارشات از شماره سفارش تعیین شده به بعد در نرم افزار حسابداری</label>
            <span class="bazara-caption">شماره اولین سفارش مورد نظر جهت ارسال به نرم افزار حسابداری را وارد نمایید.</span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
          <strong style="text-align:center">کد سفارش</strong>
            <input type="text" id="order_id_greater_than" name="order_id_greater_than" <?= $sync_order_id  ? "" : "disabled"?> value="<?= $last_order_id?>" autocomplete="off">
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>