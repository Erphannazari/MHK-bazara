<?php 
defined( 'ABSPATH' ) || exit;
$sync_interval = empty($visitor['refresh_interval']) ? '' : $visitor['refresh_interval'];
$active_auto_sync = !empty($visitor['active_auto_sync']) && $visitor['active_auto_sync'];

?>
<div class="subform">
  <table class="form-table  " data-select2-id="103">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      <tr>
        <td width="5%">
          <div class="input-switch checked">
            <input type="checkbox" class="bazara_wp_products_inte" data-validation-objects='["bazara_options_refresh_interval"]' name="bazara_intver_toggle" id="bazara_intver_toggle" <?= $active_auto_sync ? "checked" : ""?>>
            <label for="bazara_intver_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <label class="top-10">همگام سازی خودکار با نرم افزار حسابداری محک </label>
            <span class="bazara-caption">با روشن کردن این قابلیت،اطلاعات نرم افزار حسابداری به سایت به صورت اتوماتیک انتقال پیدا میکند</span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <strong style="text-align:center">بازه همگام سازی (دقیقه)</strong>
            <input type="number" id="bazara_options_refresh_interval" name="bazara_interval" class="" <?= !$active_auto_sync?'disabled' : ' '   ?> value="<?= $sync_interval?>" autocomplete="off" min="1">
            <span class="bazara-caption">عددی بزرگتر از صفر وارد کنید</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
