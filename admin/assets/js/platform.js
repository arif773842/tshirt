design.platform = 'wordpress';
jQuery(document).ready(function() {
	jQuery.ajax({
		url: base_url+'platform.php',
		dataType: 'text',
		type: 'get',
		success: function(res) {
			design.platform =res;
			// hide options non-use
			if (design.platform == 'prestashop' || design.platform == 'opencart') {
				jQuery('input[name="product[min_order]"]').parent().parent().css('display', 'none');
				jQuery('input[name="product[max_oder]"]').parent().parent().css('display', 'none');
				jQuery('select[name="product[tax]"]').parent().parent().css('display', 'none');
				jQuery('#prices-quantity').css('display', 'none');
				jQuery('button[onclick="dgUI.product.priceQuantity();"]').css('display', 'none');

				jQuery('input[name="product[hide_button_cart]"]').parent().parent().css('display', 'none');

				jQuery('.clear-line, hr').css('display', 'none');

				jQuery('#product_categories').parent().parent().css('display', 'none');
			}

			if (design.platform == 'opencart') {
				jQuery('#product_categories-lable').parent().parent().css('display', 'none');
			}
		}
	});
});
