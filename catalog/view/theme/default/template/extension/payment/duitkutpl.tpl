<?php if (count($errors) > 0): ?>
  <?php foreach ($errors as $error): ?>
	<?php ?></div>
    <div class="error"><?php echo $error ?></div>
  <?php endforeach ?>
<?php else: ?>
	<?php ?></div>
  <div class="buttons">
    <div class="pull-right">
    <input type="button" value="<?php echo $button_confirm ?>" id="button-confirm" class="btn btn-primary " data-loading-text="<?php echo $text_loading; ?>"  />
    </div>
  </div>
  <script>
  $('#button-confirm').on('click', function() {
    $.ajax({
      url: 'index.php?route=<?php echo $process_order; ?>',
      cache: false,
      beforeSend: function() {
        $('#button-confirm').button('loading');
      },
      complete: function() {
        $('#button-confirm').button('reset');
      },
      success: function(data) {
        location = data;
      }
    });
  });
  </script>
    <!-- v2 VT-Web form -->
<?php endif ?>
