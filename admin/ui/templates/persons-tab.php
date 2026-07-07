<?php 
defined( 'ABSPATH' ) || exit;
$send_person = ($visitorOption['chkCustomer'] );
$toggle_send_person = !empty($visitorOption['radioCustomer']);
$receive_person = ($visitorOption['chkCustomersMahak'] );
$guest_person = ($visitorOption['chkGuestCustomer'] );
$register_person = ($visitorOption['radioCustomer'] == BAZARA_PERSON_REGISTER );
$General_person = ($visitorOption['radioCustomer'] == BAZARA_PERSON_GENERAL );
$General_customer = empty($visitorOption['generalCustomerID']  )? '' : $visitorOption['generalCustomerID'];
$guest_customer = empty($visitorOption['guestPersonID']  ) ? '' : $visitorOption['guestPersonID'];
$group_customer = empty($visitorOption['customerGroupID']  ) ? '' : $visitorOption['customerGroupID'];


?> 
<div class="subform">
<div class="customer-section" data-select2-id="104">
  <table class="form-table  " data-select2-id="103">
    <tbody data-select2-id="102">
      <tr data-select2-id="111">
        <th scope="row" valign="top" style="vertical-align: top;"></th>
      </tr>
      <tr>
        <td width="5%">
          <div class="input-switch checked">
            <input type="checkbox" class="bazara_wp_products_inte" name="bazara_person_toggle" data-validation-objects='["bazara_guest_person","bazara_new_person_toggle"]' id="bazara_person_toggle" <?= ($send_person ? 'checked' : '')?> >
            <label for="bazara_person_toggle"></label>
            <span class="status_text yes"></span>
            <span class="status_text no"></span>
          </div>
        </td>
        <td>
          <div class="bazara-div_for_caption">
            <label for="bazara_person_toggle" class="top-10">ارسال اطلاعات مشتــریان از سایت به نرم افزار</label>
            <span class="bazara-caption">آیا تمایــل دارید مشخصات مشتریــانی که درسایت ثبت نام میکنند ویا به عنوان کاربر مهمان سفارش ثبت میکنند را به نــرم افــزار حسابداری ارسال کنید؟ (تغییرات مشتریان قبلی ارسال نخواهد شد)</span>
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
              <input type="checkbox" class="bazara_wp_products_inte" name="bazara_new_person_toggle" <?= !$send_person  ? 'disabled':''?>  data-validation-objects='["radioAllCustomer","radioPublicCustomer"]' id="bazara_new_person_toggle" <?= ($toggle_send_person ? 'checked' : '')?>>
              <label for="bazara_new_person_toggle" class="small"></label>
              <span class="status_text yes"></span>
              <span class="status_text no"></span>
            </div>
          </td>
          <td>
            <div class="bazara-div_for_caption hook">
              <label for="bazara_new_person_toggle" class="top-10">انتقال اطلاعات کاربران جدید به نــرم افزار حسابداری</label>
              <span class="bazara-caption"> اطلاعات کاربــرانی که در سایت ثبت نام می‌کنند به نـــرم افزار حسابداری منتقل میشود.</span>
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
          <td width="55%">
            <div class="bazara-div_for_caption" style="    line-height: 2.5;">
            <label><input type="radio" id="radioAllCustomer" <?= !$send_person  ? 'disabled':''?> class="customer" name="customer" value="register" data-disable-objects='["selectPerson"]' data-enable-objects='["selectGroup"]'  <?php echo $register_person  ? 'checked' : ''?> /> 
            <div class="bazara-div_for_caption hook">
              <label for="bazara_new_person_toggle" class="top-10"><?php echo esc_html__('تعریف شخص جدید در نرم افزار حسابداری','mahak-bazara') ?> </label>
              <span class="bazara-caption"> گروه مورد نظر برای ایجاد اشخاص جدید در نرم افزار حسابداری را تعیین کنید</span>
            </div>
            
          
          </label>
            </div>
          </td>
          
          <td style="vertical-align: bottom;padding-bottom: 20px!important;">
          <select name="PersonGroup" id="selectGroup" <?php echo !$register_person  ? 'disabled' : ''?> />>
                                <?php 
                               $personsGroups = get_person_group(true);
                                foreach($personsGroups as $group)
                                {?>
                                <option value="<?= $group['PersonGroupId'] ?>" <?php if ($group_customer == $group['PersonGroupId']) echo 'selected'  ?>>
                                <?= $group['Name']  ?>  
                                </option>
                                
                                <?php } ?>
                            </select>
                                </td>                  
        </tr>
        <tr>
          <td width="5%">
           
          </td>
          <td width="55%">
            <div class="bazara-div_for_caption" style="    line-height: 2.5;">
            <label><input type="radio" id="radioPublicCustomer" <?= !$send_person  ? 'disabled':''?> class="customer" name="customer" value="general"  data-enable-objects='["selectPerson"]' data-disable-objects='["selectGroup"]' <?php echo $General_person  ? 'checked' : ''?> /> <?php echo esc_html__('عدم تعریف شخص جدید در نرم افزار حسابداری و ارسال سفارشات با شخص از پیش تعریف شده در نرم افزار حسابداری','mahak-bazara') ?> </label>
            </div>
          </td>
          
          <td style="vertical-align: bottom;padding-bottom: 20px!important;">
          <select name="publicPerson" id="selectPerson" <?php echo !$General_person  ? 'disabled' : ''?> />>
                                <?php 
                                foreach($persons as $person)
                                {?>
                                <option value="<?php echo $person['PersonId'] ?>" <?php if ($General_customer == $person['PersonId']) echo 'selected'  ?>>
                                <?php echo $person['FirstName'] . ' ' . $person['LastName'] ?>  
                                </option>
                                
                                <?php } ?>
                            </select>
                                </td>                  
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
            <div class="input-switch ">
              <input type="checkbox" class="bazara_wp_products_inte" <?= !$send_person  ? 'disabled':''?> name="bazara_guest_person" data-validation-objects='["guestPerson"]' id="bazara_guest_person" <?php echo $guest_person  ? 'checked' : ''?>>
              <label for="bazara_guest_person" class="small"></label>
              <span class="status_text yes"></span>
              <span class="status_text no"></span>
            </div>
          </td>
          <td width="55%">
            <div class="bazara-div_for_caption">
              <label for="bazara_guest_person" class="top-10">انتقال اطلاعات کاربران مهمان به نــرم افزار حسابداری</label>
              <span class="bazara-caption">اطلاعات سفارشات کاربــرانی که در سایت ثبت نام نکرده اند به چه شخصی در نـــرم افزار حسابداری منتقل میشود</span>
            </div>
          </td>
          <td>
          <select name="person" <?= !$send_person  ? 'disabled':''?> id="guestPerson" <?php echo !$guest_person  ? 'disabled' : ''?>>
                                <?php 
                                foreach($persons as $person)
                                {?>
                                <option value="<?php echo $person['PersonId'] ?>" <?php if ($guest_customer == $person['PersonId']) echo 'selected'  ?>>
                                <?php echo $person['FirstName'] . ' ' . $person['LastName'] ?>  
                                </option>
                                
                                <?php } ?>
                            </select>
                                </td>                  
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
    <td width="5%">
      <div class="input-switch checked">
        <input type="checkbox" class="bazara_wp_products_inte" name="bazara_receive_person_toggle" id="bazara_receive_person_toggle" <?php echo $receive_person  ? 'checked' : ''?>>
        <label for="bazara_receive_person_toggle"></label>
        <span class="status_text yes"></span>
        <span class="status_text no"></span>
      </div>
    </td>
    <td>
      <div class="bazara-div_for_caption">
        <label for="bazara_receive_person_toggle" class="top-10">دریافت اطلاعات مشتریان از نرم افزار حسابداری به سایت</label>
        <span class="bazara-caption">آیا تمایــل دارید اطلاعات مشتریــان نــرم افزار حسابداری را به سایت منتقل کنید؟ </span>
      </div>
    </td>
  </tr>
</tbody>
</table>
</div>