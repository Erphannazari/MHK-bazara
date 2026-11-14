<?php 
defined( 'ABSPATH' ) || exit;
$sync_interval = empty($visitor['refresh_interval']) ? '' : $visitor['refresh_interval'];
$active_auto_sync = !empty($visitor['active_auto_sync']) && $visitor['active_auto_sync'];
$entity_sync_enabled = !empty($visitor['entity_sync_enabled']);
$sync_min = isset($visitor['sync_min']) ? intval($visitor['sync_min']) : 0;
$sync_max = isset($visitor['sync_max']) ? intval($visitor['sync_max']) : 100000;
$product_sync_enabled = !empty($visitor['product_sync_enabled']);
$product_sync_min = isset($visitor['product_sync_min']) ? intval($visitor['product_sync_min']) : 0;
$product_sync_max = isset($visitor['product_sync_max']) ? intval($visitor['product_sync_max']) : 10000;
$entity_sync_completed = !empty($visitor['entity_sync_completed']);

?>
<div class="subform">
  <table class="form-table  " data-select2-id="103">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
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

<div class="subform">
  <table class="form-table">
    <tbody>
      <tr>
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
      <tr>
        <td width="5%" style="vertical-align: middle;">
          <div class="input-switch checked">
            <input type="checkbox" id="bazara_entity_sync_toggle" <?= $entity_sync_enabled ? 'checked' : '' ?>>
            <label for="bazara_entity_sync_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td style="vertical-align: middle;">
          <div class="bazara-div_for_caption">
            <label for="bazara_entity_sync_toggle" class="top-10">دریافت اطلاعات از API و ذخیره در دیتابیس</label>
            <span class="bazara-caption">Entityهای محک در بازه زیر دریافت و در جداول میانی افزونه ذخیره می‌شوند.</span>
            <span class="bazara-caption">تا تکمیل موفق تمام Entityها، اجرای ماژول سینک محصولات مجاز نیست. تغییر این ماژول نیازمند رمز پشتیبان است.</span>
            <span id="bazara_entity_sync_status" class="bazara-caption" data-completed="<?= $entity_sync_completed ? '1' : '0' ?>">
              وضعیت دریافت: <?= $entity_sync_completed ? 'تکمیل‌شده' : 'تکمیل‌نشده' ?>
            </span>
          </div>
        </td>
        <td style="vertical-align: middle;">
          <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; justify-content:flex-end; margin-left:55px;">
            <div class="bazara-div_for_caption" style="flex:0 0 auto;">
              <label for="bazara_options_sync_min" class="top-10">شروع بازه دریافت</label>
              <input type="number" id="bazara_options_sync_min" value="<?= esc_attr($sync_min) ?>" autocomplete="off" min="0" <?= !$entity_sync_enabled ? 'disabled' : '' ?>>
              <span class="bazara-caption">دریافت اطلاعات از این ردیف شروع شود.</span>
            </div>
            <div class="bazara-div_for_caption" style="flex:0 0 auto;">
              <label for="bazara_options_sync_max" class="top-10">پایان بازه دریافت</label>
              <input type="number" id="bazara_options_sync_max" value="<?= esc_attr($sync_max) ?>" autocomplete="off" min="1" <?= !$entity_sync_enabled ? 'disabled' : '' ?>>
              <span class="bazara-caption">دریافت اطلاعات تا این ردیف ادامه پیدا کند.</span>
            </div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>

<div class="subform">
  <table class="form-table">
    <tbody>
      <tr>
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
      <tr>
        <td width="5%" style="vertical-align: middle;">
          <div class="input-switch checked">
            <input type="checkbox" id="bazara_product_batch_sync_toggle" data-entities-completed="<?= $entity_sync_completed ? '1' : '0' ?>" <?= $product_sync_enabled ? 'checked' : '' ?>>
            <label for="bazara_product_batch_sync_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td style="vertical-align: middle;">
          <div class="bazara-div_for_caption">
            <label for="bazara_product_batch_sync_toggle" class="top-10">سینک محصولات با ووکامرس</label>
            <span class="bazara-caption">محصولات ذخیره‌شده در دیتابیس افزونه با بازه مستقل زیر در ووکامرس همگام می‌شوند.</span>
            <span class="bazara-caption">این ماژول فقط بعد از تکمیل دریافت و در حالت خاموش بودن ماژول بالا فعال می‌شود. تغییر آن نیازمند رمز پشتیبان است.</span>
          </div>
        </td>
        <td style="vertical-align: middle;">
          <div style="display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; justify-content:flex-end; margin-left:55px;">
            <div class="bazara-div_for_caption" style="flex:0 0 auto;">
              <label for="bazara_options_product_sync_min" class="top-10">شروع بازه سینک محصولات</label>
              <input type="number" id="bazara_options_product_sync_min" value="<?= esc_attr($product_sync_min) ?>" autocomplete="off" min="0" <?= !$product_sync_enabled ? 'disabled' : '' ?>>
              <span class="bazara-caption">سینک محصولات از این ردیف شروع شود.</span>
            </div>
            <div class="bazara-div_for_caption" style="flex:0 0 auto;">
              <label for="bazara_options_product_sync_max" class="top-10">تعداد محصولات در هر اجرا</label>
              <input type="number" id="bazara_options_product_sync_max" value="<?= esc_attr($product_sync_max) ?>" autocomplete="off" min="1" max="10000" <?= !$product_sync_enabled ? 'disabled' : '' ?>>
              <span class="bazara-caption">حداکثر ۱۰۰۰۰ محصول در هر اجرا پردازش شود.</span>
            </div>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
