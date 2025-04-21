<?php echo $header; ?>
<?php echo $column_left; ?>
<?php echo $column_right; ?>
<?php echo $content_top; ?>
<div class="container" style="min-height: 300px;">
  <span style="font-size:27px;" >Transaction Summary</span>
  <hr>
  <?php if ($statusCode == '00'):?>
  <div class="alert alert-success alert-dismissible">
    Your payment is succeed
  </div>
<?php elseif ($statusCode == '01'):?>
<div class="alert alert-info alert-dismissible">
  Awaiting your payment
</div>
<?php else: ?>
<span>We noticed a problem with your order. Please do re-checkout.</span>
<span>If you think this is an error, feel free to contact our expert customer support team.</span>
<?php endif ?>
<table style="margin-bottom: 10px;">
  <tr>
    <td>Order ID</td>
    <td style="padding: 0px 5px;">:</td>
    <td><?php echo $merchantOrderId; ?></td>
  </tr>
  <tr>
    <td>Reference number</td>
    <td style="padding: 0px 5px;">:</td>
    <td><?php echo $reference; ?></td>
  </tr>
  <tr>
    <td>Amount (Rp)</td>
    <td style="padding: 0px 5px;">:</td>
    <td><?php echo $amount; ?></td>
  </tr>
  <tr>
    <td>Transaction status</td>
    <td style="padding: 0px 5px;">:</td>
    <td><?php echo $statusMessage; ?></td>
  </tr>
  <tr>
    <td>Fee</td>
    <td style="padding: 0px 5px;">:</td>
    <td><?php echo $fee; ?></td>
  </tr>
</table>
<?php if ($statusCode == '00'): ?>
<a href='<?php echo $home_url; ?>' class="btn btn-default">Browse our product</a>
<?php elseif ($statusCode == '01'): ?>
<span>Please complete your payment as instructed before. Check your email for instruction. Thank You!</span>
<?php else: ?>
<a href='<?php echo $checkout_url; ?>' class="btn btn-info">Re-Checkout</a>
<?php endif ?>
</div>
<?php echo $content_bottom; ?>
<?php echo $footer; ?>
