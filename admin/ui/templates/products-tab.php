<?php 
defined( 'ABSPATH' ) || exit;
$sync_product_toggle = !empty($visitorOption['chkProduct']) && $visitorOption['chkProduct'];
$chkExcludedProductsByCategory = !empty($visitorOption['chkExcludedProductsByCategory']) && $visitorOption['chkExcludedProductsByCategory'] ;
$categoryRadio = !empty($visitorOption['chkCategory']) && $visitorOption['chkCategory']=="cat";
$groupRadio = !empty($visitorOption['chkCategory']) && $visitorOption['chkCategory'] =="group";
$attributes_toggle = !empty($visitorOption['chkDontRemoveAttributes']) && $visitorOption['chkDontRemoveAttributes'] ;
$store_priority_toggle = !empty($visitorOption['StorePriorityToggle']) && $visitorOption['StorePriorityToggle'] ;
$visibility_variation_toggle = !empty($visitorOption['visibleVariation']) && $visitorOption['visibleVariation'] ;
$variation_visibility_type_invisible = !empty($visitorOption['chkVariationVisibility']) && $visitorOption['chkVariationVisibility'] =="invisible";
$variation_visibility_type_disabled = !empty($visitorOption['chkVariationVisibility']) && $visitorOption['chkVariationVisibility'] =="disable";
$variation_date_condition = !empty($visitorOption['variation_date_condition']) ? $visitorOption['variation_date_condition'] : 0;
$selectedInvisibleVariation = !empty($visitorOption['variationVisibilityType']) ? $visitorOption['variationVisibilityType'] : '';
$barcode_toggle = !empty($visitorOption['barcode']) && $visitorOption['barcode'];
$description_toggle = !empty($visitorOption['description']) && $visitorOption['description'];
$categoryOrGroup_toggle = !empty($visitorOption['chkCategory'] );
$store_priority_value = !empty($visitorOption['StoresSortOrder']) ? $visitorOption['StoresSortOrder'] : '';
$product_status_radio = !empty($visitorOption['publishStatus']) && $visitorOption['publishStatus'] == 'publish';
?>
<style>
    .bazara_admim_conf #s2id_bazara_VariationDateCondition {
     width: 100%!important; 
    z-index: unset;
}
    </style>
<div class="subform">
  <table class="form-table  ">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
      <tr>
        <td width="5%">
          <div class="input-switch ">
            <input type="checkbox" class="bazara_wp_products_inte" name="bazara_sync_product_toggle" data-validation-objects='["btn-check-publish","btn-check-draft","radioCat","radioGroup","bazara_barcode_toggle","bazara_attribute_toggle","bazara_cat_group_toggle","bazara_except_category","chkTitle","chkPrice","chkQuantity","chkUploadPics"]' id="bazara_sync_product_toggle" <?php echo ($sync_product_toggle ? 'checked' : '') ?>>
            <label for="bazara_sync_product_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <label for="bazara_sync_product_toggle" class="top-10">همگام سازی محصولات</label>
            <span class="bazara-caption">آیا محصولات همگام سازی شوند؟</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <div class="bazara-sub-div">
    <h4>مشخص نمایید کدام یک از ویژگی های محصولات همگام ســازی شود</h4>
    <div class="bazara-sub-div-grp">
      <label>
        <input type="checkbox" id="chkTitle" <?= !$sync_product_toggle  ? 'disabled':''?> name="chkTitle" <?= (!empty($visitorOption['chkTitle']) && $visitorOption['chkTitle'] ? 'checked' : '')?>> نام محصول </label>
      <label>
        <input type="checkbox" id="chkPrice" <?= !$sync_product_toggle || (class_exists('bazara_ratio_calculator'))  ? 'disabled':''?> name="chkPrice" <?=  (!empty($visitorOption['chkPrice']) && $visitorOption['chkPrice'] && !class_exists('bazara_ratio_calculator') ? 'checked' : '')?>> قیمت <?php if (class_exists('bazara_ratio_calculator')){?> <span class="bazara-help-tip tooltip">
          <span class="tooltiptext">امکان انتقال قیمت در این نسخه وجود ندارد</span>
        </span> <?php }else{ ?> <a onclick="jQuery('#price_tab').trigger('click')" href="javascript:void(0)">
          <i class="setting">تنظیمات</i>
        </a> <?php } ?> </label>
      <label>
        <input type="checkbox" id="chkQuantity" <?= !$sync_product_toggle  ? 'disabled':''?> name="chkQuantity" <?=  (!empty($visitorOption['chkQuantity']) && $visitorOption['chkQuantity'] ? 'checked' : '')?>> موجودی </label>
      <label>
        <input type="checkbox" id="chkUploadPics" <?= !$sync_product_toggle  ? 'disabled':''?> name="chkUploadPics" <?= (!empty($visitorOption['chkPicture']) && $visitorOption['chkPicture'] ? 'checked' : '')?>> تصاویر </label>
    </div>
    <hr>
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" name="bazara_description_toggle" <?= !$sync_product_toggle  ? 'disabled':''?> id="bazara_description_toggle" <?= ($description_toggle ? 'checked' : '') ?>>
                <label for="bazara_description_toggle" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td>
              <div class="bazara-div_for_caption">
                <label for="bazara_description_toggle" class="top-10">همگام سازی توضیحات کالا</label>
                <span class="bazara-caption">در صورتی که این گزینه فعال باشد توضحیات محصول جایگزین توضیحات قبلی خواهد شد.*** توجه داشته باشید اگر توضیحات محصول قبلا ثبت شده این گزینه را غیر فعال کنید. </span>
              </div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
    <hr />
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" name="bazara_barcode_toggle" <?= !$sync_product_toggle  ? 'disabled':''?> id="bazara_barcode_toggle" <?= ($barcode_toggle ? 'checked' : '') ?>>
                <label for="bazara_barcode_toggle" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td>
              <div class="bazara-div_for_caption">
                <label for="bazara_barcode_toggle" class="top-10">همگام سازی براساس بارکد</label>
                <span class="bazara-caption"> در صورتی که کد محصولات در نرم افزار حسابداری با شناسه محصولات تعریف شده در سایت هم خوانی ندارد میتوانید در نرم افزار حسابداری بارکد محصولات را بر اساس شناسه تعریف شده روی سایت تنظیم کرده و از آن ها برای همگام سازی محصولات استفاده کنید. </span>
              </div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>
    <hr />
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr data-select2-id="111">
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" <?= !$sync_product_toggle  ? 'disabled':''?> data-validation-objects='["radioGroup","radioCat"]' name="bazara_cat_group_toggle" id="bazara_cat_group_toggle" <?= ($categoryOrGroup_toggle ? 'checked' : '') ?>>
                <label for="bazara_cat_group_toggle" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td>
              <div class="bazara-div_for_caption">
                <label for="bazara_cat_group_toggle" class="top-10">دسته بندی محصولات</label>
                <span class="bazara-caption">برای استفاده از گروه ها و یا دسته بندی های تعریف شده برای محصولات در نرم افزار حسابداری از این گزینه استفاده نمایید.</span>
              </div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
      <div class="bazara-sub-div" style="display:inline-grid;line-height:2.5">
        <label>
          <div>
            <label>
              <input type="radio" id="radioGroup" name="category" <?= !$sync_product_toggle  ? 'disabled':''?> value="group" <?= ($groupRadio ? 'checked' : '') ?> /> <?php echo esc_html__('دسته بندی ها','mahak-bazara') ?> </label>
          </div>
        </label>
        <label>
          <div>
            <label>
              <input type="radio" id="radioCat" name="category" <?= !$sync_product_toggle  ? 'disabled':''?> value="cat" <?= ($categoryRadio ? 'checked' : '') ?> /> <?php echo esc_html__(' گروه ها','mahak-bazara') ?> </label>
          </div>
        </label>
      </div>
    </div>
    <hr>
    <div class="product-section">
      <table class="form-table  ">
        <tbody data-select2-id="102">
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" <?= !$sync_product_toggle  ? 'disabled':''?> data-validation-objects='["bazara_ExcludedProductsByCategory"]' name="bazara_except_category" id="bazara_except_category" <?php echo $chkExcludedProductsByCategory ? 'checked' : '' ?>>
                <label for="bazara_except_category" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td width="50%">
              <div class="bazara-div_for_caption">
                <label for="bazara_except_category" class="top-10">مستثنی کردن یک دسته بندی از همگام سازی موجودی</label>
                <span class="bazara-caption">برای عدم همگام سازی موجودی محصولات مورد نظر یک دسته بندی واحد را به آن ها اختصاص داده و در این قسمت انتخاب نمایید</span>
              </div>
            </td>
            <td>
              <select id="bazara_ExcludedProductsByCategory" <?= !$sync_product_toggle  ? 'disabled':''?> name="ExcludedProductsByCategory" <?php echo $chkExcludedProductsByCategory ? '' : 'disabled' ?>> <?php
                    $persons = get_persons();
                    $orderby = 'name';
                    $order = 'asc';
                    $cat_args = array(
                        'orderby'    => $orderby,
                        'order'      => $order,
                        'hide_empty' => true,
                    );
                    $product_categories = get_terms( 'product_cat', $cat_args );
                    foreach ($product_categories as $key => $category) {
                        $excludedCat = get_option('bazara_visitor_settings')['ExcludedProductsByCategory'];

                        ?> <option <?php echo ((!isset($excludedCat) ? 0 : $excludedCat) == $category->term_id ? 'selected' : '')  ?> value="

                        <?php echo $category->term_id ?>"> <?php echo $category->name ?> </option> <?php }
                    ?>
              </select>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <hr>
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" <?= !$sync_product_toggle  ? 'disabled':''?> name="bazara_attribute_toggle" id="bazara_attribute_toggle" <?= ($attributes_toggle ? 'checked' : '') ?>>
                <label for="bazara_attribute_toggle" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td>
              <div class="bazara-div_for_caption">
                <label for="bazara_attribute_toggle" class="top-10">عدم حذف ویژگی</label>
                <span class="bazara-caption">در صورت فعال بودن این گزینه کلیه ویژگی های که روی کالای متغیر توسط کاربر ثبت شده حذف نخواهد شد.در غیر این صورت کلیه ویژگی های نرم افزار جایگزین خواهد شد</span>
              </div>
              <div class="bazara-sub-div"></div>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>

    <hr>
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="5%">
              <div class="input-switch ">
                <input type="checkbox" class="bazara_wp_products_inte" <?= !$sync_product_toggle  ? 'disabled':''?> name="bazara_anbar_priority" data-validation-objects='["StorePriorityTable"]' id="bazara_anbar_priority" <?= ($store_priority_toggle ? 'checked' : '') ?>>
                <label for="bazara_anbar_priority" class="small"></label>
                <span class="status_text yes"></span>
                <span class="status_text no"></span>
              </div>
            </td>
            <td>
              <div class="bazara-div_for_caption">
                <label for="bazara_anbar_priority" class="top-10">موجودی انبار های قابل فروش</label>
                <span class="bazara-caption">انبارهای مورد نظر جهت محسابه موجودی کالا و اولویت آن ها را مشخص نمایید</span>
              </div>
            </td>
            <td></td>
          </tr>
          
        </tbody>
      </table>
      <table class="dig-reg-fields" id="StorePriorityTable" <?=$store_priority_toggle ? '' : 'disabled'?>>
      <input type="hidden" id="dig_sortorder" name="StorePriorityOrders" value="<?= $store_priority_value ?>">
      <tbody>
      <?php 
          $stores = explode(',',$store_priority_value);
          foreach(get_stores() as $store){
            if (!in_array($store['StoreId'],$stores)) continue;
          ?>
          
          <tr id="<?= $store['StoreId']?>">
            <td><input type="checkbox" class="chk_store" id="chk_store_<?= $store['StoreId']?>" value="<?= $store['StoreId']?>" /></td>
            <td >
            <div class="icon-drag icon-drag-dims dig_cust_field_drag dig_cust_default_fields_drag"></div>
            <input type="text" value="<?= $store['Name']?>" />
            </td>
          </tr>
          
          <?php } ?>
          </tbody>
      </table>
      <hr>
      <table class="dig-reg-fields-not-ordered" id="NotPriorityTable">
      <tbody>
      <?php 
          foreach(get_stores() as $store){
            if (in_array($store['StoreId'],$stores)) continue;

          ?>
          <tr id="<?= $store['StoreId']?>">
            <td><input type="checkbox" class="chk_store" id="chk_store_<?= $store['StoreId']?>" value="<?= $store['StoreId']?>" /></td>
            <td >
            <div class="icon-drag icon-drag-dims dig_cust_field_drag dig_cust_default_fields_drag"></div>
            <input type="text" value="<?= $store['Name']?>" />
            </td>
          </tr>
          
          <?php } ?>
          </tbody>
      </table>
    </div>
    <hr>
    <div class="product-section">
      <table class="form-table  ">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <?php
                             $properties = array_values(get_properties());
            ?>
            
            <td width="100%">
                <label for="bazara_visibility_variations" class="top-10">ویــژگی های محصولات</label><br/>
                <span class="bazara-caption">ویژگی های محوصلات به انتخاب شما غیر قابل نمایش خواهند شد</span>
            </td>
            <td></td>
          </tr>
        </tbody>
      </table>
      <div class="" style="display:inline-grid;line-height:2.5">



          <div class="uk-margin">
              <fieldset>
                <?php 
                foreach ($properties as $p){
                ?>
                    <legend><?=$p->Title?></legend>

                    <label for="name">نمایش در برگه محصول</label>
                    <?php
                    $foundCols = [];
                    if (is_array($selectedInvisibleVariation)){
                        $checkPropertyTypeExist = array_column($selectedInvisibleVariation,$p->PropertyDescriptionId);
                        $foundCols = array_values($checkPropertyTypeExist);
                    }
                    ?>
                    <input class="" type="checkbox" value="invisible"  <?= (bazara_in_array("invisible",$foundCols) || empty($selectedInvisibleVariation)) ? 'checked' : ''?>  name="variationVisibilityType[invisible][<?= $p->PropertyDescriptionId ?>]" >


                    <label for="email">انتخاب به عنوان متغیر:</label>
                    <input class="" type="checkbox" <?php if ($p->DataType == BAZARA_PROPERTY_DATE_TYPE){ ?> id="for_variation_date" <?php } ?> value="for_variation"  <?= (bazara_in_array("for_variation",$foundCols) || empty($selectedInvisibleVariation))  ? 'checked' : ''?>  name="variationVisibilityType[for_variation][<?= $p->PropertyDescriptionId ?>]" >


                    <?php if ($p->DataType == BAZARA_PROPERTY_DATE_TYPE){ ?>

                        <select id="bazara_VariationDateCondition" <?= !$sync_product_toggle || bazara_in_array("for_variation",$foundCols) ? 'disabled':''?> style="width:100%!important" name="variationDateCondition" >
                            <option value="1" <?=($variation_date_condition == 1 ? 'selected' : '' ) ?>>انتخاب متغیر به صورت تصادفی</option>
                            <option value="2" <?= ($variation_date_condition == 2 ? 'selected' : '') ?>>انتخاب متغیر بر اساس بزرگترین تاریخ</option>
                            <option value="3" <?= ($variation_date_condition == 3 ? 'selected' : '') ?>>انتخاب متغیر بر اساس کوچکترین تاریخ</option>
                        </select>

                    <?php } ?>
                <?php } ?>
              </fieldset>

        </div>
      </div>
    </div>
    <hr>
    <div class="product-section">
      <table class="form-table  " style="width:50%">
        <tbody>
          <tr>
            <th scope="row" valign="top" style="vertical-align: top;"></th>
          </tr>
          <tr>
            <td width="20%">
              <div class="bazara-div_for_caption">
                <label class="top-10">وضعیت محصولات پس از انتقال به سایت </label>
              </div>
            </td>
            <td width="10%" align="center">
              <input type="radio" class="btn-check" name="btn-check-status" <?= !$sync_product_toggle  ? 'disabled':''?> value="publish" id="btn-check-publish" <?= ($product_status_radio ? 'checked' : '') ?> autocomplete="off">
              <label class="btn btn-primary  
																															<?= ($product_status_radio ? 'btnchecked' : '') ?>" for="btn-check-publish">منتشر شده </label>
            </td>
            <td width="10%" align="right">
              <input type="radio" class="btn-check" name="btn-check-status" <?= !$sync_product_toggle  ? 'disabled':''?> value="draft" id="btn-check-draft" <?= (!$product_status_radio ? 'checked' : '') ?> autocomplete="off">
              <label class="btn btn-primary  
																																<?= (!$product_status_radio ? 'btnchecked' : '') ?>" for="btn-check-draft">پیش نویس </label>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>