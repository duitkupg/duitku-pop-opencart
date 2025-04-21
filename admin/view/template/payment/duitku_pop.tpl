<?php echo $header;?> <?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-payment" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
        <h1><?php echo $heading_title; ?></h1>
        <ul class="breadcrumb">
          <?php foreach ($breadcrumbs as $breadcrumb) { ?>
          <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
          <?php } ?>
        </ul>
      </div>
    </div>
    <div class="container-fluid">
      <?php if (isset($error['error_warning'])) { ?>
      <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error['error_warning']; ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
      </div>
      <?php } ?>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
        </div>
        <div class="panel-body">
          <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">

            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-mode"><?php echo $entry_status; ?></label>
              <div class="col-sm-10">
                <select name="duitku_pop_status" id="input-mode" class="form-control">
                  <?php $options = array('1' => $text_enabled, '0' => $text_disabled) ?>
                  <?php foreach ($options as $key => $value): ?>
                  			<option value="<?php echo $key; ?>"
                          <?php if ($key == $duitku_pop_status) echo 'selected' ?> >
                          <?php echo $value; ?>
                        </option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>


            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_display_name"><?php echo $entry_display_name; ?></label>
              <div class="col-sm-10">
                <input type="text" name="duitku_pop_display_name" value="<?php echo $duitku_pop_display_name; ?>" placeholder="<?php echo $entry_display_name; ?>" id="payment_duitku_pop_display_name" class="form-control" />
                <?php if (isset($error['display_name'])) {?>
                <div class="text-danger"><?php echo $error['display_name']; ?></div>
                <?php }?>
              </div>
            </div>

            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_plugin_status"><?php echo $entry_plugin_status; ?></label>
              <div class="col-sm-10">
                <select name="duitku_pop_plugin_status" id="payment_duitku_pop_plugin_status" class="form-control">
                  <?php $options = array('sandbox' => 'Sandbox', 'production' => 'Production') ?>
                  <?php foreach ($options as $key => $value): ?>
									<option value="<?php echo $key ?>" <?php if ($key == $duitku_pop_plugin_status) echo 'selected' ?> ><?php echo $value ?></option>
									<?php endforeach ?>
                </select>
              </div>
            </div>

            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_ui_mode"><?php echo $entry_ui_mode; ?></label>
              <div class="col-sm-10">
                <select name="duitku_pop_ui_mode" id="payment_duitku_pop_ui_mode" class="form-control">
                  <?php $options = array('popup' => 'Popup', 'redirect' => 'Redirect') ?>
                  <?php foreach ($options as $key_ui => $value_ui): ?>
									<option value="<?php echo $key_ui ?>" <?php if ($key_ui == $duitku_pop_ui_mode) echo 'selected' ?> ><?php echo $value_ui ?></option>
									<?php endforeach ?>
                </select>
              </div>
            </div>

            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_endpoint"><?php echo $entry_endpoint ?></label>
              <div class="col-sm-10">
                <input type="text" name="duitku_pop_endpoint" value="<?php echo $duitku_pop_endpoint ?>" placeholder="<?php echo $entry_endpoint; ?>" id="payment_duitku_pop_endpoint" class="form-control" />
                <?php if (isset($error['endpoint'])) {?>
                <div class="text-danger"><?php echo $error['endpoint']; ?></div>
                <?php }?>
              </div>
            </div>

            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_merchant"><?php echo $entry_merchant; ?></label>
              <div class="col-sm-10">
                <input type="text" name="duitku_pop_merchant" value="<?php echo $duitku_pop_merchant ?>" placeholder="<?php echo $entry_merchant; ?>" id="payment_duitku_pop_merchant" class="form-control" />
                <?php if (isset($error['merchant_code'])) { ?>
                <div class="text-danger"><?php echo $error['merchant_code']; ?></div>
                <?php }?>
              </div>
            </div>


            <div class="form-group required">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_api_key"><?php echo $entry_api_key; ?></label>
              <div class="col-sm-10">
                <input type="text" name="duitku_pop_api_key" value="<?php echo $duitku_pop_api_key; ?>" placeholder="<?php echo $entry_api_key; ?>" id="payment_duitku_pop_api_key" class="form-control" />
                <?php if (isset($error['api_key'])) {?>
                <div class="text-danger"><?php echo $error['api_key']; ?></div>
                <?php }?>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="payment_duitku_pop_expiry_period"><?php echo $entry_expiry_period; ?></label>
              <div class="col-sm-10">
                <input type="text" name="duitku_pop_expiry_period" value="<?php echo $duitku_pop_expiry_period; ?>" placeholder="The validity period of the transaction before it expires. (e.g 1 - 1440 ( min ))" id="payment_duitku_pop_expiry_period" class="form-control" />
              </div>
            </div>

            <?php foreach($statuses as $status): ?>
            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-order-status">
                <?php echo ${'entry_'.$status} ?>
              </label>
              <div class="col-sm-10">
                <select name="<?php echo $status; ?>" id="<?php echo $status; ?>" class="form-control">
                  <?php foreach($order_statuses as $order_status):?>
                  <?php if($order_status['order_status_id'] ==  ${$status}){?>
                  <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                  <?php }else{?>
                  <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                  <?php }?>
                  <?php endforeach?>
                </select>
              </div>
            </div>
            <?php endforeach?>

            <div class="form-group">
              <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
              <div class="col-sm-10">
                <select name="duitku_pop_geo_zone_id" id="input-geo-zone" class="form-control">
                  <option value="0"><?php echo $text_all_zones; ?></option>
                  <?php foreach($geo_zones as $geo_zone): ?>
                  <?php if ($geo_zone['geo_zone_id'] == $duitku_pop_geo_zone_id) {?>
                  <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                  <?php }else{?>
                  <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                  <?php }?>
                 <?php endforeach?>
                </select>
              </div>
            </div>

            <div>
              <center><font size="1">version v1.0.0</font></center>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
  <?php echo $footer; ?>
