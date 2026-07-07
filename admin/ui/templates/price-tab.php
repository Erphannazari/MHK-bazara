<?php 
defined( 'ABSPATH' ) || exit;
$price = ($visitorOption['chkRegularPrice'] || $visitorOption['chkSalePrice']);
$regular_price = ($visitorOption['chkRegularPrice'] );
$sale_price = ($visitorOption['chkSalePrice'] );
$date_first_cond = ($visitorOption['dateFirstCond'] );
$date_first_cond_price = ($visitorOption['dateFirstCondPrice'] );
$date_first_cond_discount = ($visitorOption['dateFirstCondDiscount'] );

$date_second_cond = ($visitorOption['dateSecondCond'] );
$date_second_cond_price = ($visitorOption['dateSecondCondPrice'] );
$date_second_cond_discount = ($visitorOption['dateSecondCondDiscount'] );

$date_third_cond = ($visitorOption['dateThirdCond'] );
$date_third_cond_price = ($visitorOption['dateThirdCondPrice'] );
$date_third_cond_discount = ($visitorOption['dateThirdCondDiscount'] );


$multiPrice_RegularLevel_Wholesaler = ($visitorOption['bazara_regular_multiprice_price_select'] );
$multiPrice_RegularLevel_Wholesaler_role = ($visitorOption['bazara_regular_multiprice_role_select'] );

$multiPrice_cheque_Wholesaler = ($visitorOption['bazara_regular_multiprice_cheque_select'] );
$multiPrice_cheque_Wholesaler_role = ($visitorOption['bazara_regular_multiprice_role_cheque'] );


$multiPrice_discount_Wholesaler = ($visitorOption['bazara_regular_multiprice_discount_price_select'] );
$multiPrice_discount_Wholesaler_role = ($visitorOption['bazara_regular_multiprice_role_discount'] );




$radioPercentDiscount = ($visitorOption['discount'] == "percent_discount" );
$radioPriceDiscount = ($visitorOption['discount'] == "price_discount" );
$DiscountPriceOrPercent = ($visitorOption['DiscountPriceOrPercent'] );
$selectRegularPrice = ($visitorOption['RegularPrice'] );
$PriceLevels = ['یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه','ده'];
$PriceLevelsNumber = [1,2,3,4,5,6,7,8,9,10];

$MonthLevels = ['یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه','ده','یازده','دوازده'];
$MonthLevelsNumber = [1,2,3,4,5,6,7,8,9,10,11,12,13];

$selectCurrencySoftware = ($visitorOption['selectCurrencySoftware'] );
$selectCurrencyPlugin = ($visitorOption['selectCurrencyPlugin'] );


?> 
<div class="subform">
<div class="price-section" data-select2-id="104">
  <table class="form-table  " data-select2-id="103">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
      <tr>
        <td width="5%">
          <div class="input-switch ">
            <input type="checkbox" class="bazara_wp_products_inte" name="bazara_price_toggle" data-validation-objects='["bazara_regular_price_toggle","bazara_sale_price_toggle"]' id="bazara_price_toggle" <?php echo $price ? 'checked' : ''?> >
            <label for="bazara_price_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <label for="bazara_price_toggle" class="top-10"> همگام سازی قیمت ها</label>
            <span class="bazara-caption">با فعال سازی این بخش، انتقال قیمت ها بین نـــرم افـزار حسابداری و سایت انجام میگیـــرد</span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <br>
  <div class="bazara-sub-div">
 

    <table class="form-table  " data-select2-id="103">
      <tbody data-select2-id="102">
        <tr data-select2-id="111">
          <th scope="row" valign="top" style="vertical-align: top;"></th>
        </tr>
        <tr>
          <td width="5%">
            <div class="input-switch ">
              <input type="checkbox" class="bazara_wp_products_inte" name="bazara_regular_price_toggle" <?= !$price  ? 'disabled':''?> data-validation-objects='[<?= ('"'. implode('","',array_map('add_prefix_id_validation',$PriceLevelsNumber)).'"') ?>]' id="bazara_regular_price_toggle" <?php echo $regular_price  ? 'checked' : ''?>>
              <label for="bazara_regular_price_toggle" class="small"></label>
              <span class="status_text yes"></span>
              <span class="status_text no"></span>
            </div>
          </td>
          <td width="35%">
            <div class="bazara-div_for_caption">
              <label for="bazara_regular_price_toggle" class="top-10">قیمت عادی</label>
              <span class="bazara-caption">
          تعیین نمایید کدام یک از سطوح قیمتی نرم افزار به عنوان قیمت عادی محصولات به سایت ارسال شود(در صورت غیر فعال کردن این گزینه سطح قیمتی پیش فرض هر محصول به عنوان قیمت عادی محصول در نظر گرفته میشود)
            </span>
            </div>
          </td>
          <div id="reg-alpha-section">
          <?php 
          
          for($i=0;$i<sizeof($PriceLevels);$i++)
          {
            $PriceSelected = false;
            if ($selectRegularPrice == $i+1 ) $PriceSelected = true;

            ?>
        <td width="5%" class="check-alpha" align="center"><input type="radio" class="btn-check"  value="<?= $i+1 ?>" name="btn-reg-price" id="btn-reg-price-<?= $PriceLevelsNumber[$i] ?>" <?php echo $PriceSelected ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary alphabet <?= $PriceSelected ? 'btnchecked' : '' ?>" for="btn-reg-price-<?= $PriceLevelsNumber[$i] ?>"><?= $PriceLevels[$i] ?></label></td> 
<?php }
          ?>
          </div>
         <td width="20%"></td>
        </tr>
      </tbody>
    </table>
     <br>
<br>
    <table class="form-table  " data-select2-id="103">
      <tbody data-select2-id="102">
        <tr data-select2-id="111">
          <th scope="row" valign="top" style="vertical-align: top;"></th>
        </tr>
        <tr>
          <td width="5%">
            <div class="input-switch checked">
              <input type="checkbox" class="bazara_wp_products_inte" name="bazara_sale_price_toggle" <?= !$price  ? 'disabled':''?> data-validation-objects='["radioPercentDiscount","radioPriceDiscount",<?= ('"'. implode('","',array_map('add_prefix_id_priceDiscount',$PriceLevelsNumber)).'"') ?>,<?= ('"'. implode('","',array_map('add_prefix_id_percentDiscount',$PriceLevelsNumber)).'"') ?>]' id="bazara_sale_price_toggle" <?php echo $sale_price  ? 'checked' : ''?>>
              <label for="bazara_sale_price_toggle" class="small"></label>
              <span class="status_text yes"></span>
              <span class="status_text no"></span>
            </div>
          </td>
          <td>
            <div class="bazara-div_for_caption hook">
              <label for="bazara_sale_price_toggle" class="top-10">قیمت فروش ویــژه</label>
              <span class="bazara-caption">میتوانید به دو صورت استفاده از سطح قیمتی مورد نظر و یا تعیین سطح تخفیف درصدی ، قیمت فروش ویژه را مشخص نمایید.</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <table class="form-table  " data-select2-id="103">
      <tbody data-select2-id="102">
        <tr data-select2-id="111">
          <th scope="row" valign="top" style="vertical-align: top;"></th>
        </tr>
        <tr>
          <td width="5%">
           
          </td>
          <td width="35%">
            <div class="bazara-div_for_caption" style="    line-height: 2.5;">
            <label><input type="radio" id="radioPercentDiscount"  name="discount" <?= !$price || !$sale_price  ? 'disabled':''?> value="percent_discount" data-disable-objects='[<?= ('"'. implode('","',array_map('add_prefix_id_priceDiscount',$PriceLevelsNumber)).'"') ?>]' data-enable-objects='[<?= ('"'. implode('","',array_map('add_prefix_id_percentDiscount',$PriceLevelsNumber)).'"') ?>]' <?= $radioPercentDiscount ? 'checked' : '' ?> /><?php echo esc_html__(' سطوح تخفیف درصدی','mahak-bazara') ?></label>
            <span class="bazara-caption">
              قیمت فروش ویژه با کسر تخفیف درصدی تعیین شده از قیمت عادی با توجه به تنظیمات قیمت عادی محاسبه و اعمال میشود.
            </span>

                                    </div>
          </td>

          <?php 
          $percentSelected = false;
          for($i=0;$i<sizeof($PriceLevels);$i++)
          {?>
        <td width="5%" align="center">
            <?php if ($i < 4) {
              $percentSelected = false;
              if ($radioPercentDiscount && $DiscountPriceOrPercent == ($i + 1) ) $percentSelected = true;
              ?>
        <input type="radio" class="btn-check" id="btn-percent-discount-<?= $PriceLevelsNumber[$i] ?>" <?= !$sale_price || !$radioPercentDiscount ? 'disabled ' : '' ?> value="<?= $i+1 ?>"" name="btn-percent-discount" <?php echo $percentSelected ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary alphabet <?php echo $percentSelected ? 'btnchecked' : '' ?>" for="btn-percent-discount-<?= $PriceLevelsNumber[$i] ?>"><?= $PriceLevels[$i] ?></label>
                        
                        <?php }
          ?>
                    </td> 
<?php }
          ?>
         <td width="20%"></td>
                 
        </tr>
        <tr>
          <td width="5%">
           
          </td>
          <td width="35%">
            <div class="bazara-div_for_caption" style="    line-height: 2.5;">
            <label>
                                <input type="radio" id="radioPriceDiscount" name="discount" value="price_discount" <?= !$price || !$sale_price  ? 'disabled':''?> data-disable-objects='[<?= ('"'. implode('","',array_map('add_prefix_id_percentDiscount',$PriceLevelsNumber)).'"') ?>]' data-enable-objects='[<?= ('"'. implode('","',array_map('add_prefix_id_priceDiscount',$PriceLevelsNumber)).'"') ?>]' <?= $radioPriceDiscount ? 'checked' : '' ?>  /><?php echo esc_html__(' سطح قیمتی','mahak-bazara') ?>
                              </label>
                                    </div>
          </td>

          <?php 
          $discountPriceSelected = false;

          for($i=0;$i<sizeof($PriceLevels);$i++)

          {
            $discountPriceSelected = false;
            if ($radioPriceDiscount && $DiscountPriceOrPercent == $i + 1) $discountPriceSelected = true;

            ?>
        <td width="5%" align="center"><input type="radio" <?= !$price || !$sale_price || !$radioPriceDiscount  ? 'disabled':''?> class="btn-check" value="<?= $i+1  ?>"" name="btn-price-discount" id="btn-price-discount-<?= $PriceLevelsNumber[$i] ?>" <?php echo $discountPriceSelected ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary alphabet <?php echo $discountPriceSelected ? 'btnchecked' : '' ?>" for="btn-price-discount-<?= $PriceLevelsNumber[$i] ?>"><?= $PriceLevels[$i] ?></label></td> 
<?php }
          ?>
         <td width="20%"></td>
                 
        </tr>
      </tbody>
    </table>



</div>
  </div>
      </div>
 <div class="subform">

<table class="form-table  " data-select2-id="103">
<tbody data-select2-id="102">
  <tr data-select2-id="111">
    <th scope="row" valign="top" style="vertical-align: top;"></th>
  </tr>
  <tr>
   
    <td>
      <div class="bazara-div_for_caption">
        <label class="top-10"> واحد پولی </label>
        <span class="bazara-caption"> واحد پولی استفاده شده در سایت و نــرم افزار حسابداری را مشخص نمایید</span>
      </div>
    </td>
  </tr>
</tbody>
</table>

<table class="form-table  "style="width:40%">
                    <tbody >
                    <tr>
                        <th scope="row" valign="top" style="vertical-align: top;"></th>
                    </tr>
                    <tr>
                        <td width="20%">
                        <div class="bazara-div_for_caption">
                            <label class="top-10"> واحد پولی نـــرم افزار </label>
                        </div>
                        </td>
                        <td width="10%" align="center">
                        <input type="radio" class="btn-check" name="btn-check-soft-currency"  value="rial" id="btn-check-softwarec-rial" <?= $selectCurrencySoftware == "rial" ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary currency <?= $selectCurrencySoftware == "rial" ? 'btnchecked' : '' ?>" for="btn-check-softwarec-rial"> ریال </label>
                        </td>
                        <td width="10%" align="right">
                        <input type="radio" class="btn-check " name="btn-check-soft-currency"  value="toman" id="btn-check-softwarec-toman" <?= $selectCurrencySoftware == "toman" ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary currency <?= $selectCurrencySoftware == "toman" ? 'btnchecked' : '' ?>" for="btn-check-softwarec-toman"> تومان </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <table class="form-table  "style="width:40%">
                    <tbody >
                    <tr>
                        <th scope="row" valign="top" style="vertical-align: top;"></th>
                    </tr>
                    <tr>
                        <td width="20%">
                        <div class="bazara-div_for_caption">
                            <label class="top-10"> واحد پولی سایت </label>
                        </div>
                        </td>
                        <td width="10%" align="center">
                        <input type="radio" class="btn-check"  name="btn-check-site-currency" value="rial" id="btn-check-sitec-rial" <?= $selectCurrencyPlugin == "rial" ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary currency <?php echo $selectCurrencyPlugin == "rial" ? 'btnchecked' : '' ?>" for="btn-check-sitec-rial"> ریال </label>
                        </td>
                        <td width="10%" align="right">
                        <input type="radio" class="btn-check"  name="btn-check-site-currency" value="toman" id="btn-check-sitec-toman" <?= $selectCurrencyPlugin == "toman" ? 'checked' : '' ?> autocomplete="off">
                        <label class="btn btn-primary currency <?php echo $selectCurrencyPlugin == "toman"? 'btnchecked' : '' ?>" for="btn-check-sitec-toman"> تومان </label>
                        </td>
                    </tr>
                    </tbody>
                </table>
</div>


<?php
if (class_exists("sell_simple_with_date_variants")){ ?>
<div class="subform">

<table class="form-table  " data-select2-id="103">
<tbody data-select2-id="102">
  <tr data-select2-id="111">
    <th scope="row" valign="top" style="vertical-align: top;"></th>
  </tr>
  <tr>
   
    <td>
      <div class="bazara-div_for_caption">
        <label class="top-10"> سطح قیمتی برای تاریخ مصرف کالا</label>
        <span class="bazara-caption"> سطح قیمتی برای نزدیکترین تاریخ مصرف کالا را تعیین کنید</span>
      </div>
    </td>
  </tr>
</tbody>
</table>

<table class="form-table  " data-select2-id="103">
                                            <tbody data-select2-id="102">
                                                <tr data-select2-id="111">
                                                <th scope="row" valign="top" style="vertical-align: top;"></th>
                                                </tr>
                                                
                                                    <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" class="bazara_wp_products_inte " <?= !empty($date_first_cond) ? 'checked' : ''?> data-validation-objects='["date_first_select","date_first_select_price","date_first_select_discount"]' id="date_first_toggle" name="date_first_toggle">
                                                    <label for="date_first_toggle" class="small">>سطح قیمتی برای نزدیکترین تاریخ</label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="date_first_toggle" >
                                                      <select <?= empty($date_first_cond) ? 'disabled' : '' ?>  name="date_first_select" id="date_first_select">
                                                      <option value="<?php echo 13 ?>"  <?=  ($date_first_cond ==  13? 'selected' : '') ?>>
                                                      بیشتر از دوازده ماه
                                                    </option>
                                                   <?php 
                                                    for($i=1;$i<12;$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_first_cond ==  $i + 1 ? 'selected' : '') ?>>
                                                    کمتر از  <?php echo $MonthLevels[$i]?> ماه 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                    <td width="15%"><label for="date_first_toggle" >
                                                      <select <?=empty($date_first_cond) ? 'disabled' : '' ?>  name="date_first_select_price" id="date_first_select_price">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_first_cond_price ==  $i + 1? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                <td width="15%"><label for="date_first_select_discount" >
                                                      <select <?=empty($date_first_cond) ? 'disabled' : '' ?>  name="date_first_select_discount" id="date_first_select_discount">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_first_cond_discount ==  $i + 1? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                
                                              </tr>
                                                

                                              <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" class="bazara_wp_products_inte " <?= !empty($date_second_cond) ? 'checked' : ''?> data-validation-objects='["date_second_select","date_second_select_price","date_second_select_discount"]' id="date_second_toggle" name="date_second_toggle">
                                                    <label for="date_second_toggle" class="small">>سطح قیمتی برای نزدیکترین تاریخ</label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="date_second_toggle" >
                                                      <select <?= empty($date_second_cond) ? 'disabled' : '' ?>  name="date_second_select" id="date_second_select">
                                                      <option value="<?php echo 13 ?>"  <?=  ($date_second_cond ==  14? 'selected' : '') ?>>
                                                      بیشتر از دوازده ماه
                                                    </option>
                                                   <?php 
                                                    for($i=1;$i<12;$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_second_cond ==   $i + 1? 'selected' : '') ?>>
                                                    کمتر از  <?php echo $MonthLevels[$i]?> ماه 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                    <td width="15%"><label for="date_second_toggle" >
                                                      <select <?=empty($date_second_cond) ? 'disabled' : '' ?>  name="date_second_select_price" id="date_second_select_price">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_second_cond_price ==   $i + 1? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                <td width="15%"><label for="date_second_select_discount" >
                                                      <select <?=empty($date_second_cond) ? 'disabled' : '' ?>  name="date_second_select_discount" id="date_second_select_discount">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_second_cond_discount ==   $i + 1? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                
                                              </tr>
                                                
                                              <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" class="bazara_wp_products_inte " <?= !empty($date_third_cond) ? 'checked' : ''?> data-validation-objects='["date_third_select","date_third_select_price","date_third_select_discount"]' id="date_third_toggle" name="date_third_toggle">
                                                    <label for="date_third_toggle" class="small">سطح قیمتی برای نزدیکترین تاریخ</label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="date_third_toggle" >
                                                      <select <?= empty($date_third_cond) ? 'disabled' : '' ?>  name="date_third_select" id="date_third_select">
                                                      <option value="<?php echo 13 ?>"  <?=  ($date_third_cond ==  14? 'selected' : '') ?>>
                                                      بیشتر از دوازده ماه
                                                    </option>
                                                   <?php 
                                                    for($i=1;$i<12;$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_third_cond ==   $i + 1? 'selected' : '') ?>>
                                                    کمتر از  <?php echo $MonthLevels[$i]?> ماه 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                    <td width="15%"><label for="date_third_toggle" >
                                                      <select <?=empty($date_third_cond) ? 'disabled' : '' ?>  name="date_third_select_price" id="date_third_select_price">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_third_cond_price ==   $i + 1 ? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>

                                                <td width="15%"><label for="date_third_select_discount" >
                                                      <select <?=empty($date_third_cond) ? 'disabled' : '' ?>  name="date_third_select_discount" id="date_third_select_discount">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($date_third_cond_discount ==   $i + 1 ? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                              </tr>
                                            </tbody>
                                            </table>
              
</div>
<?php } ?>
<?php
if (class_exists("ChequeShipping")){ ?>

<div class="subform">

<table class="form-table  " data-select2-id="103">
<tbody data-select2-id="102">
  <tr data-select2-id="111">
    <th scope="row" valign="top" style="vertical-align: top;"></th>
  </tr>
  <tr>
   
    <td>
      <div class="bazara-div_for_caption">
        <label class="top-10"> سطح قیمتی برای گروه مشتریان</label>
        <span class="bazara-caption"> سطح قیمتی برای گروه مشتریان را تعیین کنید</span>
      </div>
    </td>
  </tr>
</tbody>
</table>

<table class="form-table  " data-select2-id="103">
                                            <tbody data-select2-id="102">
                                                <tr data-select2-id="111">
                                                <th scope="row" valign="top" style="vertical-align: top;"></th>
                                                </tr>
                                                
                                              
                                                

                                                
                                              <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" class="bazara_wp_products_inte " <?= !empty($multiPrice_cheque_Wholesaler) ? 'checked' : ''?> data-validation-objects='["bazara_regular_multiprice_cheque_select","bazara_regular_multiprice_role_cheque"]' id="bazara_regular_multiprice_cheque_toggle" name="bazara_regular_multiprice_cheque_toggle">
                                                    <label for="bazara_regular_multiprice_cheque_toggle" class="small">سطح قیمتی برای خرید چکی</label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="bazara_regular_multiprice_cheque_select" >
                                                    <select <?=empty($multiPrice_cheque_Wholesaler) ? 'disabled' : '' ?>  name="bazara_regular_multiprice_cheque_select" id="bazara_regular_multiprice_cheque_select">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($multiPrice_cheque_Wholesaler ==   $i + 1 ? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                    <td width="15%"><label for="bazara_regular_multiprice_role_cheque" >
                                                      <select <?=empty($multiPrice_cheque_Wholesaler_role) ? 'disabled' : '' ?>  name="bazara_regular_multiprice_role_cheque" id="bazara_regular_multiprice_role_cheque">
                                                      <?php 
                                                    foreach(get_wp_roles() as $role)
                                                    {?>
                                                    <option value="<?=$role ?>"  <?=  ($multiPrice_cheque_Wholesaler_role ==   $role? 'selected' : '') ?>>
                                                    <?=$role ?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>

                                                
                                              </tr>


                                              <tr><td width="5%">
                                                    <div class="input-switch ">
                                                    <input type="checkbox" class="bazara_wp_products_inte " <?= !empty($multiPrice_discount_Wholesaler) ? 'checked' : ''?> data-validation-objects='["bazara_regular_multiprice_discount_price_select","bazara_regular_multiprice_role_discount"]' id="bazara_regular_multiprice_discount_toggle" name="bazara_regular_multiprice_discount_toggle">
                                                    <label for="bazara_regular_multiprice_discount_toggle" class="small">سطح قیمتی برای خرید در جشنواره</label>
                                                    <span class="status_text yes"></span>
                                                    <span class="status_text no"></span>
                                                    </div>

                                                    </td>
                                                    <td width="15%"><label for="bazara_regular_multiprice_discount_price_select" >
                                                    <select <?=empty($multiPrice_discount_Wholesaler) ? 'disabled' : '' ?>  name="bazara_regular_multiprice_discount_price_select" id="bazara_regular_multiprice_discount_price_select">
                                                    <?php 
                                                    for($i=0;$i<sizeof($PriceLevels);$i++)
                                                    {?>
                                                    <option value="<?php echo $i+1 ?>"  <?=  ($multiPrice_discount_Wholesaler ==   $i + 1 ? 'selected' : '') ?>>
                                                    سطح قیمتی <?php echo $PriceLevels[$i]?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>
                                                    <td width="15%"><label for="bazara_regular_multiprice_role_discount" >
                                                      <select <?=empty($multiPrice_discount_Wholesaler_role) ? 'disabled' : '' ?>  name="bazara_regular_multiprice_role_discount" id="bazara_regular_multiprice_role_discount">
                                                      <?php 
                                                    foreach(get_wp_roles() as $role)
                                                    {?>
                                                    <option value="<?=$role ?>"  <?=  ($multiPrice_discount_Wholesaler_role ==   $role ? 'selected' : '') ?>>
                                                    <?=$role ?> 
                                                    </option>
                                                    
                                                    <?php } ?>
                                                </select></label></td>

                                                
                                              </tr>
                                            </tbody>
                                            </table>
              
</div>
<?php } ?>
