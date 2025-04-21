<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
      <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <!-- breadcrumb -->

  <?php if (isset($error['error_warning'])): ?>
    <div class="warning"><?php echo $error['error_warning']; ?></div>
  <?php endif; ?>
  <!-- error -->

  <div class="box">
    <div class="heading">
      <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <!-- heading -->

    <div class="content">
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" class="form-horizontal">
        <table class="form">

          <tr>
            <td><?php echo $entry_status; ?></td>
            <td>
              <select name="duitku_pop_status">
                <?php $options = array('1' => $text_enabled, '0' => $text_disabled) ?>
                <?php foreach ($options as $key => $value): ?>
                  <option value="<?php echo $key; ?>"
                    <?php if ($key == $duitku_pop_status) echo 'selected' ?> >
                    <?php echo $value; ?>
                  </option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>

          <tr>
            <td><span class="required">*</span> <?php echo $entry_display_name; ?></td>
            <td>
              <input type="text" name="duitku_pop_display_name" value="<?php echo $duitku_pop_display_name; ?>" placeholder="<?php echo $entry_display_name; ?>" id="payment_duitku_pop_display_name" />
              <?php if (isset($error['display_name'])) {?>
                <span class="error"><?php echo $error['display_name']; ?></span>
              <?php }?>
            </td>
          </tr>

          <tr>
            <td><span class="required">*</span> <?php echo $entry_plugin_status; ?></td>
            <td>
              <select name="duitku_pop_plugin_status" id="payment_duitku_pop_plugin_status">
                <?php $options = array('sandbox' => 'Sandbox', 'production' => 'Production') ?>
                <?php foreach ($options as $key => $value): ?>
                  <option value="<?php echo $key ?>" <?php if ($key == $duitku_pop_plugin_status) echo 'selected' ?> ><?php echo $value ?></option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>

          <tr>
            <td><span class="required">*</span> <?php echo $entry_ui_mode; ?></td>
            <td>
              <select name="duitku_pop_ui_mode" id="payment_duitku_pop_ui_mode">
                <?php $options_ui = array('popup' => 'Popup', 'redirect' => 'Redirect') ?>
                <?php foreach ($options_ui as $key_ui => $value_ui): ?>
                  <option value="<?php echo $key_ui ?>" <?php if ($key_ui == $duitku_pop_ui_mode) echo 'selected' ?> ><?php echo $value_ui ?></option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>

          <tr>
            <td><span class="required">*</span> <?php echo $entry_endpoint ?></td>
            <td>
              <input type="text" name="duitku_pop_endpoint" value="<?php echo $duitku_pop_endpoint ?>" placeholder="<?php echo $entry_endpoint; ?>" id="payment_duitku_pop_endpoint" />
              <?php if (isset($error['endpoint'])) {?>
                <span class="error"><?php echo $error['endpoint']; ?></span>
              <?php }?>
            </td>
          </tr>

          <tr>
            <td><span class="required">*</span> <?php echo $entry_merchant; ?></td>
            <td>
              <input type="text" name="duitku_pop_merchant" value="<?php echo $duitku_pop_merchant ?>" placeholder="<?php echo $entry_merchant; ?>" id="payment_duitku_pop_merchant" />
              <?php if (isset($error['merchant_code'])) { ?>
                <div class="error"><?php echo $error['merchant_code']; ?></div>
              <?php }?>
            </td>
          </tr>


          <tr>
            <td><span class="required">*</span> <?php echo $entry_api_key; ?></td>
            <td>
              <input type="text" name="duitku_pop_api_key" value="<?php echo $duitku_pop_api_key; ?>" placeholder="<?php echo $entry_api_key; ?>" id="payment_duitku_pop_api_key" />
              <?php if (isset($error['api_key'])) {?>
                <span class="error"><?php echo $error['api_key']; ?></span>
              <?php }?>
            </td>
          </tr>

          <tr>
            <td><?php echo $entry_expiry_period; ?></td>
            <td>
              <input type="text" name="duitku_pop_expiry_period" value="<?php echo $duitku_pop_expiry_period; ?>" placeholder="<?php echo $entry_expiry_period; ?>" id="payment_duitku_pop_expiry_period" />
            </td>
          </tr>

          <?php foreach($statuses as $status): ?>
            <tr>
              <td>
                <?php echo ${'entry_'.$status} ?>
              </td>
              <td>
                <select name="<?php echo $status; ?>" id="<?php echo $status; ?>">
                  <?php foreach($order_statuses as $order_status):?>
                    <?php if($order_status['order_status_id'] ==  ${$status}){?>
                      <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                    <?php }else{?>
                      <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                    <?php }?>
                  <?php endforeach?>
                </select>
              </td>
            </tr>
          <?php endforeach?>

          <tr>
            <td><?php echo $entry_geo_zone; ?></td>
            <td>
              <select name="duitku_pop_geo_zone_id" id="input-geo-zone">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach($geo_zones as $geo_zone): ?>
                  <?php if ($geo_zone['geo_zone_id'] == $duitku_pop_geo_zone_id) {?>
                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                  <?php }else{?>
                    <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                  <?php }?>
                <?php endforeach?>
              </select>
            </td>
          </tr>
        </table>
      </form>
      <div>
        <center><font size="1">version v1.0.0</font></center>
      </div>
    </div>
  </div>
</div>

<?php echo $footer; ?>
