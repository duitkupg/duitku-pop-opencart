<?php

class ModelExtensionPaymentDuitkuPop extends Model
{

  public function getMethod($address, $total)
  {

    $this->load->language('payment/duitku_pop');

    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('duitku_pop_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

    if ($this->config->get('duitku_pop_total') > 0 && $this->config->get('duitku_pop_total') > $total) {
      $status = false;
    } elseif (!$this->config->get('duitku_pop_geo_zone_id')) {
      $status = true;
    } elseif ($query->num_rows) {
      $status = true;
    } else {
      $status = false;
    }


    //Currently Duitku only accepts transactin in indonesian rupiah (IDR)
    $currencies = array(
      'IDR',
    );

    if (!in_array(strtoupper($this->session->data['currency']), $currencies)) {
      $status = false;
    }

    $method_data = array();

    if ($status) {
      $method_data = array(
        'code'       => 'duitku_pop',
        'title'      => $this->config->get('duitku_pop_display_name'),
        'sort_order' => $this->config->get('duitku_pop_sort_order'),
        'terms'    => ''
      );
    }

    return $method_data;
  }

  public function addToken($data)
  {
    $this->db->query("INSERT INTO `tokens` SET order_id = '" . $data['order_id'] . "', token_merchant = '" . $data['token_merchant'] . "', token_browser = '" . $data['token_browser'] . "'");
  }

  public function getTokenMerchant($orderId)
  {
    $merchant_query = $this->db->query("SELECT token_merchant FROM `tokens` WHERE order_id = '" . $orderId . "'");
    $token_merchant = '0';

    if ($merchant_query->num_rows) {
      $token_merchant = $merchant_query->row['token_merchant'];
    }

    return $token_merchant;
  }

  public function getTokenBrowser($orderId)
  {
    $browser_query = $this->db->query("SELECT token_browser FROM `tokens` WHERE order_id = '" . $orderId . "'");
    $token_browser = '0';

    if ($browser_query->num_rows) {
      $token_browser = $browser_query->row['token_browser'];
    }

    return $token_browser;
  }
}
