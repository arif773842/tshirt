design.platform = 'wordpress';
jQuery(document).on('ini.design', function(event) {
	if (jQuery('#product-list-colors .bg-colors').length > 1) {
		jQuery('#e-change-product-color').css('display', 'block');
	} else {
		jQuery('#e-change-product-color').css('display', 'none');
	}
	jQuery.ajax({
		url: siteURL+'platform.php',
		dataType: 'text',
		type: 'get',
		success: function(res) {
			design.platform =res;

			if (design.platform == 'wordpress') {
				jQuery.ajax({
					url: siteURL+'ajax.session.php',
					dataType: 'json',
					success: function(res) {
						if (res != false && res != '') {
						    user_id = res.id;
						}
					}
				});
			}
		}
	});
});

jQuery(document).on('after.load.design', function(event, data) {
	if (jQuery('#product-list-colors .bg-colors').length > 1) {
		jQuery('#e-change-product-color').css('display', 'block');
	} else {
		jQuery('#e-change-product-color').css('display', 'none');
	}
});

jQuery(document).on('product.change.design', function(event, product) {
	if (jQuery('#product-list-colors .bg-colors').length > 1) {
		jQuery('#e-change-product-color').css('display', 'block');
	} else {
		jQuery('#e-change-product-color').css('display', 'none');
	}
});
