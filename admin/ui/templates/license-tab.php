<?php 
defined( 'ABSPATH' ) || exit;

?>
<div class="subform">
<div class="visitor-section">
  <div style="
		<?= (!empty($visitor['username']) ? '' : 'display:none' ) ?>">
    <table style="width:30%">
      <tbody>
        <tr>
          <td data-id="site_package_number" style="font-size: 26px;padding-bottom:10px"> <?= $visitor['PackageNo'] ?>
              <?php if ($visitor['CreditDay']>=0) { ?>
                <img src="<?= PLUGIN_DIR_URL?>assets/img/Verified.png" style="width: 20px;">
              <?php } else{ ?>
                <img src="<?= PLUGIN_DIR_URL?>assets/img/Verified.png" style="width: 20px;">
              <?php } ?>
          </td>
          <td style="text-align: left;">
            <a onclick="change_visitor_login()">تغییر</a>
          </td>
        </tr>
      </tbody>
    </table>
    <table style="width:30%;background-color: #ebebeb;line-height: 2.5;border: 2px solid #cecbcb;">
      <tbody>
        <tr>
            <td data-id="site_name" style="color: #686262;" colspan="2">
                <span><?= $visitor['site_name'] ?? '' ?></span>
            </td>
        </tr>
        <tr>
            <td data-id="site_name" style="font-size: 15px; color: #686262;">شناسه بانک اطلاعاتی:
                <span style="font-size: 18px;padding-right: 5px"><?= $visitor['DatabaseId'] ?? '' ?></span>
            </td>
        </tr>
        <tr style="border-top: 1px solid #000;">
            <td data-id="site_expiredate" style="font-size: 15px; border-top: 0.3px solid #000;color: #a99e9e;border-color: #a99e9e;">مانده اعتبار :
                <span style="font-size: 18px;padding-right: 5px; color: <?= $visitor['CreditDay']>=0?'green':'red'; ?>"> <?= $visitor['CreditDay']??'' ?>  روز</span>
            </td>
        </tr>
      </tbody>
    </table>
  </div>
  <div style="
			<?=(empty($visitor['username']) ? '' : 'display:none') ?>">
    <input type="hidden" name="security" id="security" value="<?= wp_create_nonce('bazara_security') ?>">
    <h1>اتصال به نرم افزار حسابداری محک</h1>
    <h3>نام کاربری و رمز عبور خود را وارد نمایید.</h3>
    <br />
    <br />
    <table class="form-table  ">
      <tbody data-select2-id="102">
        <tr>
          <th scope="row" valign="top" style="vertical-align: top;"></th>
        <tr class="ippanelcred ">
          <th scope="row">
            <label for="bazara_username"> نام کاربری </label>
          </th>
          <td>
            <input type="text" id="bazara_options_username" name="username" class="regular-text" value="<?= empty($visitor['username']) ? '' : $visitor['username'] ?>" autocomplete="off" bazara-optional="0" required="required">
          </td>
        </tr>
        <tr class="ippanelcred ">
          <th scope="row">
            <label for="bazara_password"> رمز عبور </label>
          </th>
          <td>
            <input type="password" id="bazara_options_password" name="password" class="regular-text" value="<?= empty($visitor['password']) ? '' : $visitor['password'] ?>" autocomplete="off" bazara-optional="0" required="required">
          </td>
        </tr>
        <tr class="ippanelcred ">
          <th></th>
          <td>
            <div id="btn_save_setting" class="bazara_call_test_api_btn bazara-button-spinner success" id="bazara-library-sync-button"><label>اتصال</label></div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
</div>