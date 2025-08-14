<?php
namespace Opencart\Catalog\Controller\Extension\DuitkuPop\Payment;

use Symfony\Component\Validator\Constraints\NotNull;

class DuitkuPop extends \Opencart\System\Engine\Controller
{

  public function index()
  {
    $data['errors'] = array();
    $data['button_confirm'] = $this->language->get('button_confirm');

    $data['text_loading'] = $this->language->get('text_loading');

    $data['process_order'] = 'extension/duitku_pop/payment/duitku_pop'. $this->separator() .'process_order';

    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitkutpl')) {
        return $this->load->view($this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitkutpl', $data);
      } else {
        return $this->load->view('default/template/extension/duitku_pop/payment/duitkutpl', $data);
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      return $this->load->view('extension/duitku_pop/payment/duitkutpl', $data);
    }
  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order()
  {
    $log_filename = 'duitku-pop.log';
    $log = new \Opencart\System\Library\Log($log_filename);
    $this->load->model('extension/duitku_pop/payment/duitku_pop');
    $this->load->model('checkout/order');
    //$this->load->model('total/shipping');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('payment_duitku_pop_merchant');
    $api_key = $this->config->get('payment_duitku_pop_api_key');
    $expiry_period = $this->config->get('payment_duitku_pop_expiry_period');
    $ui_mode = $this->config->get('payment_duitku_pop_ui_mode');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = trim($this->currency->format($order_info['total'], 'IDR', false, false));
    //$signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);

    $tstamp = round(microtime(true) * 1000);
    $mcode = $merchant_code;
    $header_signature = hash('sha256', $mcode . $tstamp . $api_key);

    // Prepare Parameters
    $params = array(
      'merchantOrderId' => (string)$order_id,
      'merchantUserInfo' => $order_info['email'],
      'expiryPeriod' => (int)$expiry_period,
      'paymentAmount' => intval($order_total), //transform order into integer
      'paymentMethod' => "",
      'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
      'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
      'email' => $order_info['email'],
      'phoneNumber' => $order_info['telephone'],
      'returnUrl' => $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'landing_redir'),
      'callbackUrl' => $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'payment_notification'),
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
        'price' => $shipping_data['cost'],
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

    // echo "<pre>".var_export($params)."</pre>";
    if ($this->config->get('payment_duitku_pop_plugin_status') == 'production'){
      $baseUrl = 'https://api-prod.duitku.com';
    } else {
      $baseUrl = 'https://api-sandbox.duitku.com';
    }

    $url = $baseUrl.'/api/merchant/createInvoice';

    $header = array(
          'Content-Type: application/json',
          'x-duitku-signature: ' . $header_signature,
          'x-duitku-timestamp: ' . $tstamp,
          'x-duitku-merchantCode: ' . $mcode
    );

    if (extension_loaded('curl')) {
      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);

        $respond = json_decode($server_output);
        $log->write('URL: ' . json_encode($url));
        $log->write('Request : ' . json_encode($params, JSON_PRETTY_PRINT));
        $log->write('Header : ' . json_encode($header,JSON_PRETTY_PRINT));
        $log->write('Response : ' . json_encode($respond != null ? $respond : $server_output ,JSON_PRETTY_PRINT));
        
        unset($this->session->data['order_id']);

        if (isset($respond->statusCode) && $respond->statusCode == '00') {
          //Add record order if success, if not success it wont recorded
          $this->model_checkout_order->addHistory($order_id, $this->config->get('payment_duitku_pop_pending_mapping'), 'Duitku payment pending.', true, false);
          $this->cart->clear();
          if ($ui_mode == 'popup') {
            $redirUrl = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'proceed', 'reference=' . $respond->reference, true);
            $this->response->setOutput($redirUrl);
          } else {
            $this->response->setOutput($respond->paymentUrl);
          }
        } else {
          $failureUrl = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'failure', true);
          $this->response->setOutput($failureUrl);
        }
      } catch (\Exception $e) {
        $log->write('Error : ' . $e->getMessage());
        $failureUrl = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'failure', true);
        $this->response->setOutput($failureUrl);
      }
    } else {
      throw new \Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }
  }

  /*
  * assume there is no failure in bank transfer but waiting for transfer
  */
  public function pending() {
    $this->load->language('extension/duitku_pop/payment/duitku_pop');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_failure'] = $this->language->get('text_pending');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    //$data['checkout_url'] = $this->url->link('checkout/cart');

     if(version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_checkout_va')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_checkout_va', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/duitku_pop/payment/duitku_checkout_va', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/duitku_pop/payment/duitku_checkout_va', $data));
    }        
  }

  /*
  * when failed create transaction or failed to pay redirect to here
  */
  public function failure() {
    $this->load->language('extension/duitku_pop/payment/duitku_pop');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_failure'] = $this->language->get('text_failure');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    $data['checkout_url'] = $this->url->link('checkout/cart');

     if(version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_checkout_failure.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_checkout_failure.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/duitku_pop/payment/duitku_checkout_failure', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/duitku_pop/payment/duitku_checkout_failure', $data));
    }        
  }

  public function proceed()
  {
    $this->load->model('checkout/order');
    $this->load->model('extension/duitku_pop/payment/duitku_pop');
    $this->load->language('extension/duitku_pop/payment/duitku_pop');

    $this->document->setTitle($this->language->get('heading_title'));
    $data['heading_title'] = $this->language->get('heading_title');
    $get_str = $_SERVER['QUERY_STRING'];
    $string_q = parse_str($get_str, $get_array);
    $plugin_status = $this->config->get('payment_duitku_pop_plugin_status');

    if ($plugin_status == 'sandbox') {
      $url_lib = 'https://app-sandbox.duitku.com/lib/js/duitku.js';
    } elseif ($plugin_status == 'production') {
      $url_lib = 'https://app-prod.duitku.com/lib/js/duitku.js';
    }

    $data['reference_number'] = $get_array['reference'];
    $data['url_library'] = $url_lib;
    $data['url_redirect'] = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'landing_redir');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    //$data['checkout_url'] = $this->url->link('checkout/cart');
    if (version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_pop.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/duitku_pop/payment/duitku_pop.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/duitku_pop/payment/duitku_pop', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/duitku_pop/payment/duitku_pop', $data));
    }
  }

  public function landing_redir()
  {
    $this->load->model('checkout/order');
    $this->load->model('extension/duitku_pop/payment/duitku_pop');

    if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '00') {
      $redirUrl = $this->url->link('checkout/success');
      $this->response->redirect($redirUrl);
    } else if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '01') {
      //if capture or pending or challenge or settlement, redirect to order received page
      $pendingUrl = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'pending', true);
      $this->response->redirect($pendingUrl);
    } else if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '02') {
      $failureUrl = $this->url->link('extension/duitku_pop/payment/duitku_pop'. $this->separator() .'failure', true);
      $this->response->redirect($failureUrl);

    } else if (isset($_GET['order_id']) && !isset($_GET['resultCode'])) {
      // if customer click "back" button, redirect to checkout page again
      $redirUrl = $this->url->link('checkout/cart');
      $this->response->redirect($redirUrl);
    }
  }

  public function payment_notification()
  {
    $log_filename = 'duitku-pop.log';
    $log = new \Opencart\System\Library\Log($log_filename);
    $this->load->model('checkout/order');
    $this->load->model('extension/duitku_pop/payment/duitku_pop');

    if (empty($_REQUEST['resultCode']) || empty($_REQUEST['merchantOrderId']) || empty($_REQUEST['signature'])) {
      header("HTTP/1.1 404 Not Found");
      $log->write("Wrong query string please contact admin.");
      die;
    }

    if (!empty($_REQUEST['merchantOrderId'])) {
      $order_id = stripslashes($_REQUEST['merchantOrderId']);
    } else {
      $str = $_SERVER['QUERY_STRING'];
      parse_str($str, $output);
      if (!empty($output['merchantOrderId'])) {
        $order_id = $output['merchantOrderId'];
      } else {
        $order_id = $output['merchantOrderId'];
      }
    }

    $status = stripslashes($_REQUEST['resultCode']);

    $merchantcode = $this->config->get('payment_duitku_pop_merchant');
    $apikey = $this->config->get('payment_duitku_pop_api_key');

    $signatureCheck = md5($merchantcode . intval($_REQUEST['amount']) . $_REQUEST['merchantOrderId'] . $apikey);

    $order_info = $this->model_checkout_order->getOrder($order_id);
    
    //check if order id is in the database
    if (!$order_info) {
      header("HTTP/1.1 404 Not Found");
      $log->write("Orders Not Found for Order : ".$order_id );
      die;
    }

    $current_status_name = $order_info['order_status_id'];
    if ($current_status_name == $this->config->get('payment_duitku_pop_success_mapping')){
      header("HTTP/1.1 200");
      $log->write("Order Already Completed for Order : ".$order_id );
      die;
    }

    if ($_REQUEST['signature'] != $signatureCheck){
      header("HTTP/1.1 401 Unauthorized");
      $log->write("Wrong Signature for Order : ".$order_id );
      die;
    }

    $log->write("Callback Recieved : " . json_encode($_REQUEST, JSON_PRETTY_PRINT));

    //Check Transaction
    if ($this->config->get('payment_duitku_pop_plugin_status') == 'production'){
      $baseUrl = 'https://api-prod.duitku.com';
    } else {
      $baseUrl = 'https://api-sandbox.duitku.com';
    }
    $url = $baseUrl . '/api/merchant/transactionStatus';
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
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close($ch);
        $respond = json_decode($server_output);
        $log->write("URL Check transaction: " . $url); 
        $log->write("Request Check Transaction : " . json_encode($params, JSON_PRETTY_PRINT));   
        $log->write("Response Check Transaction : " . json_encode($respond != null ? $respond : $server_output ,JSON_PRETTY_PRINT));

        $respondStatus = json_decode($server_output);
        if ($respondStatus->statusCode == '00' && $status == '00') {
          header("HTTP/1.1 200 OK");
          $log->write("Callback Success for Order : ".$order_id );
          $this->model_checkout_order->addHistory($order_id, $this->config->get('payment_duitku_pop_success_mapping'), 'Duitku payment success.');
        } else {
          header("HTTP/1.1 200 OK");
          $log->write("Callback Failed for Order : ".$order_id );
          $this->model_checkout_order->addHistory($order_id, $this->config->get('payment_duitku_pop_failure_mapping'), 'Duitku payment failed.', true, false);
        }
      } catch (\Exception $e) {
        $this->log->write('Error : ' . $e->getMessage());
        //echo "Validation Error";
      }
    } else {
      throw new \Exception("Duitku payment need curl extension, please enable curl extension in your web server", "duitku");
    }
  }

  private function separator(): string
  {
    if (VERSION >= '4.0.2.0') {
      return '.';
    }

    return '|';
  }
}
