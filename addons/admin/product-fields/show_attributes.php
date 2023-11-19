<?php
/**
 * @author tshirtecommerce - www.tshirtecommerce.com
 * @date: 2015-11-21
 *
 * @copyright  Copyright (C) 2015 tshirtecommerce.com. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 *
 */

?>
<hr>
<div class="form-group">
	<div class="col-sm-12">
		<label class="checkbox-inline hide_button_cart">
			<?php if(isset($data->hide_button_cart) && $data->hide_button_cart == 1) { ?>
				<input type='checkbox' name='product[hide_button_cart]' checked="checked" value="1">
			<?php } else { ?>
				<input type='checkbox' name='product[hide_button_cart]' value="1">
			<?php } ?>
			<span><?php echo lang('product_hide_button_cart'); ?></span>
		</label>
	
		<label class="checkbox-inline show_attribute">
			<?php if(isset($data->show_attribute) && $data->show_attribute == 1) { ?>
				<input type='checkbox' name='product[show_attribute]' checked="checked" value="1">
			<?php } else { ?>
				<input type='checkbox' name='product[show_attribute]' value="1">
			<?php } ?>
			<span><?php echo lang('product_show_attribute'); ?></span>
		</label>
	</div>
</div>
<script type="text/javascript">
	jQuery('.hide_button_cart').change(function(){
		showAttribute();
	});
	function showAttribute()
	{
		if(jQuery('.hide_button_cart').children('input').is(':checked'))
			jQuery('.show_attribute').hide();
		else
			jQuery('.show_attribute').show();
	}
	showAttribute();
</script>