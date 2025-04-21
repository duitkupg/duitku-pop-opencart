<?php

class ControllerPaymentDuitkuPop extends Controller
{

  public function index()
  {

    $this->data['errors'] = array();
    $this->data['button_confirm'] = $this->language->get('button_confirm');

    $this->data['text_loading'] = $this->language->get('text_loading');

    $this->data['process_order'] = 'payment/duitku_pop/process_order';

    // CODE HERE IF LOWER
    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitkutpl.tpl')) {
      $this->template = $this->config->get('config_template') . '/template/payment/duitkutpl.tpl';
    } else {
      $this->template = 'default/template/payment/duitkutpl.tpl';
    }

    $this->render();
  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order()
  {
    $this->load->model('payment/duitku_pop');
    $this->load->model('checkout/order');
    $this->load->model('total/shipping');
    $this->load->language('payment/duitku_pop');

    $this->data['errors'] = array();

    $this->data['button_confirm'] = $this->language->get('button_confirm');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('duitku_pop_merchant');
    $ui_mode = $this->config->get('duitku_pop_ui_mode');
    $api_key = $this->config->get('duitku_pop_api_key');
    $expiry_period = $this->config->get('duitku_pop_expiry_period');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = $def_curr == 'IDR' ? $order_info['total'] : $this->currency->convert($order_info['total'], $def_curr, 'IDR');
    //$signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);

    $tstamp = round(microtime(true) * 1000);
    $mcode = $merchant_code;
    $header_signature = hash('sha256', $mcode . $tstamp . $api_key);

    // Prepare Parameters
    $params = array(
      'merchantOrderId' => (string)$order_id,
      'merchantUserInfo' => $order_info['email'],
      'paymentAmount' => intval($order_total), //transform order into integer
      'paymentMethod' => "",
      'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
      'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
      'email' => $order_info['email'],
      'expiryPeriod' => intval($expiry_period),
      'phoneNumber' => $order_info['telephone'],
      'returnUrl' => $this->url->link('payment/duitku_pop/payment_notification'),
      'callbackUrl' => $this->url->link('payment/duitku_pop/payment_notification'),
    );

    $customer_detail = array(
      'firstName' => $order_info['firstname'],
      'lastName' => $order_info['lastname'],
      'email' => $order_info['email'],
      'phoneNumber' => $order_info['telephone'],
    );

    $billing_address = array(
      'firstName' => (string)$order_info['payment_firstname'],
      'lastName' => (string)$order_info['payment_lastname'],
      'address' => (string)$order_info['payment_address_1'] . ', ' . (string)$order_info['payment_address_2'] . ', ' . (string)$order_info['payment_city'] . ', ' . (string)$order_info['payment_zone'] . ' ' . (string)$order_info['payment_postcode'] . ', ' . (string)$order_info['payment_country'],
      'city' => (string)$order_info['payment_city'],
      'postalCode' => (string)$order_info['payment_postcode'],
      'phone' => (string)$order_info['telephone'],
      'countryCode' => (string)$order_info['payment_iso_code_2'],
    );

    if ($this->cart->hasShipping()) {
      $shipping_address = array(
        'firstName' => (string)$order_info['shipping_firstname'],
        'lastName' => (string)$order_info['shipping_lastname'],
        'address' => (string)$order_info['shipping_address_1'] . ', ' . (string)$order_info['shipping_address_2'] . ', ' . (string)$order_info['shipping_city'] . ', ' . (string)$order_info['shipping_zone'] . ' ' . (string)$order_info['shipping_postcode'] . ', ' . (string)$order_info['shipping_country'],
        'city' => (string)$order_info['shipping_city'],
        'postalCode' => (string)$order_info['shipping_postcode'],
        'phone' => (string)$order_info['telephone'],
        'countryCode' => (string)$order_info['shipping_iso_code_2'],
      );
    } else {
      $shipping_address = $billing_address;
    }

    $products = $this->cart->getProducts();

    $item_details = array();

    foreach ($products as $product) {
      $item = array(
        'price'    => (int)($product['price'] * $product['quantity']),
        'quantity' => (int)$product['quantity'],
        'name'     => $product['name']
      );
      $item_details[] = $item;
    }

    if ($this->cart->hasShipping()) {
      $shipping_data = $this->session->data['shipping_method'];
      if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
        $shipping_data['cost'] = $this->tax->calculate(
          $shipping_data['cost'],
          $shipping_data['tax_class_id'],
          $this->config->get('config_tax')
        );
      }

      $shipping_item = array(
        'price' => (int)$shipping_data['cost'],
        'quantity' => 1,
        'name' => 'Shipping Fee'
      );
      $item_details[] = $shipping_item;
    }

    $amount_price = 0;
    foreach ($item_details as $item) {
      $amount_price += $item['price'];
    }

    if ($amount_price != $order_total) {
      $coupon_item = array(
        'price'    => (int)($order_total - $amount_price),
        'quantity' => 1,
        'name'     => 'Coupon'
      );
      $item_details[] = $coupon_item;
    }

    $customer_detail['shippingAddress'] = $shipping_address;
    $customer_detail['billingAddress'] = $billing_address;
    $params['customerDetail'] = $customer_detail;
    $params['itemDetails'] = $item_details;

    // echo "<pre>".var_export($item_details)."</pre>";

    $url = $this->config->get('duitku_pop_endpoint') . '/api/merchant/createInvoice';

    if (extension_loaded('curl')) {
      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'x-duitku-signature: ' . $header_signature,
          'x-duitku-timestamp: ' . $tstamp,
          'x-duitku-merchantCode: ' . $mcode
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        $respond = json_decode($server_output);
        if (isset($respond->statusCode)) {
          if ($respond->statusCode == '00') {
            if ($ui_mode == 'popup') {
              $redirUrl = $this->url->link('payment/duitku_pop/proceed', 'reference=' . $respond->reference, true);
              $this->response->setOutput($redirUrl);
            } else {
              $this->response->setOutput($respond->paymentUrl);
            }
          }
        } else {
          $this->session->data['current_amount'] = intval($order_total);
          $this->session->data['warning_message'] = $server_output;
          $warningUrl = $this->url->link('payment/duitku_pop/warning', true);
          $this->response->setOutput($warningUrl);
        }
      } catch (Exception $e) {
        $this->data['errors'][] = $e->getMessage();
        error_log($e->getMessage());
        echo $e->getMessage();
      }
    } else {
      throw new Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }
  }

  public function warning()
  {
    $this->load->language('payment/duitku_pop');

    if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
      $this->data['base_url'] = $this->config->get('config_ssl');
    } else {
      $this->data['base_url'] = $this->config->get('config_url');
    }

    $this->document->setTitle($this->language->get('warning_title'));
    $this->data['heading_title'] = $this->language->get('warning_title');
    $this->data['warning_message'] = $this->session->data['warning_message'];
    $this->data['current_amount'] = $this->session->data['current_amount'];
    $this->children = array(
      'common/column_left',
      'common/column_right',
      'common/content_top',
      'common/content_bottom',
      'common/footer',
      'common/header'
    );

    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku_pop_warning.tpl')) {
      $this->template = $this->config->get('config_template') . '/template/payment/duitku_pop_warning.tpl';
    } else {
      $this->template = 'default/template/payment/duitku_pop_warning.tpl';
    }

    $this->response->setOutput($this->render(true));
  }

  public function proceed()
  {
    $this->load->model('checkout/order');
    $this->load->model('payment/duitku_pop');
    $this->load->language('payment/duitku_pop');

    if (empty($_REQUEST['amp;reference'])) {
      $response = 'Reference number not found.';
      throw new \Exception($response);
    }

    $this->document->setTitle($this->language->get('heading_title'));
    $this->data['heading_title'] = $this->language->get('heading_title');

    $plugin_status = $this->config->get('duitku_pop_plugin_status');

    if ($plugin_status == 'sandbox') {
      $url_lib = 'https://app-sandbox.duitku.com/lib/js/duitku.js';
    } elseif ($plugin_status == 'production') {
      $url_lib = 'https://app-prod.duitku.com/lib/js/duitku.js';
    }

    $this->data['reference_number'] = $_REQUEST['amp;reference'];
    $this->data['url_library'] = $url_lib;
    $this->data['url_redirect'] = $this->url->link('payment/duitku_pop/payment_notification');
    //$data['checkout_url'] = $this->url->link('checkout/cart');

    $this->children = array(
      'common/column_left',
      'common/column_right',
      'common/content_top',
      'common/content_bottom',
      'common/footer',
      'common/header'
    );

    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku_pop.tpl')) {
      $this->template = $this->config->get('config_template') . '/template/payment/duitku_pop.tpl';
    } else {
      $this->template = 'default/template/payment/duitku_pop.tpl';
    }

    $this->response->setOutput($this->render(true));
  }

  public function payment_notification()
  {
    $this->load->model('checkout/order');
    $this->load->model('payment/duitku_pop');

    if (!empty($_REQUEST['merchantOrderId'])) {
      $order_id = $_REQUEST['merchantOrderId'];
    } else {
      $str = $_SERVER['QUERY_STRING'];
      parse_str($str, $output);
      if (!empty($output['amp;merchantOrderId'])) {
        $order_id = $output['amp;merchantOrderId'];
      } else {
        $order_id = $output['merchantOrderId'];
      }
    }

    $order_info = $this->model_checkout_order->getOrder($order_id);
    $merchantcode = $this->config->get('duitku_pop_merchant');
    $apikey = $this->config->get('duitku_pop_api_key');
    $endpoint = $this->config->get('duitku_pop_endpoint');
    $url = $endpoint . '/api/merchant/transactionStatus';
    $signature = md5($merchantcode . $order_id . $apikey);
    $params = array(
      'merchantCode' => $merchantcode,
      'merchantOrderId' => $order_id,
      'signature' => $signature
    );

    if (extension_loaded('curl')) {
      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);

        $respondStatus = json_decode($server_output);
        if ($respondStatus->statusCode == '00') {
          $this->data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $this->data['reference'] = $respondStatus->reference;
          $this->data['amount'] = $respondStatus->amount;
          $this->data['statusCode'] = $respondStatus->statusCode;
          $this->data['statusMessage'] = $respondStatus->statusMessage;
          $this->data['fee'] = $respondStatus->fee;

          $order_status_id = $this->config->get('duitku_pop_success_mapping');
          $this->cart->clear();
        } elseif ($respondStatus->statusCode == '01') {
          $this->data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $this->data['reference'] = $respondStatus->reference;
          $this->data['amount'] = $respondStatus->amount;
          $this->data['statusCode'] = $respondStatus->statusCode;
          $this->data['statusMessage'] = $respondStatus->statusMessage;
          $this->data['fee'] = $respondStatus->fee;

          $order_status_id = $this->config->get('duitku_pop_pending_mapping');
          $this->cart->clear();
        } elseif ($respondStatus->statusCode == '02') {
          $this->data['merchantOrderId'] = $respondStatus->merchantOrderId;
          $this->data['reference'] = $respondStatus->reference;
          $this->data['amount'] = $respondStatus->amount;
          $this->data['statusCode'] = $respondStatus->statusCode;
          $this->data['statusMessage'] = $respondStatus->statusMessage;
          $this->data['fee'] = $respondStatus->fee;

          $order_status_id = $this->config->get('duitku_pop_failure_mapping');
        }

        if (!$order_info['order_status_id']) {
          $this->model_checkout_order->confirm($order_id, $order_status_id);
        } else {
          $this->model_checkout_order->update($order_id, $order_status_id);
        }
      } catch (Exception $e) {
        $this->data['errors'][] = $e->getMessage();
        error_log($e->getMessage());
        echo $e->getMessage();
      }
    } else {
      throw new Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }

    $this->data['checkout_url'] = $this->url->link('checkout/cart');
    $this->data['home_url'] = $this->url->link('common/home');

    $this->children = array(
      'common/column_left',
      'common/column_right',
      'common/content_top',
      'common/content_bottom',
      'common/footer',
      'common/header'
    );

    // CODE HERE IF LOWER
    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/result_transaction.tpl')) {
      $this->template = $this->config->get('config_template') . '/template/payment/result_transaction.tpl';
    } else {
      $this->template = 'default/template/payment/result_transaction.tpl';
    }

    $this->response->setOutput($this->render(true));
  }
}
