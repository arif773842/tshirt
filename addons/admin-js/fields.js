/* @deprecated */
jQuery(document).on('select.item.design', function(event, e) {
	var top = '0', right = '0', w = jQuery('#dg-help-functions').outerWidth();
	top = jQuery('#dg-help-functions').css('top');
	right = jQuery('#dg-help-functions').css('right');
	right = right.replace('px' ,'');
	right = parseInt(right) + w + 50;
	right += 'px';
	jQuery('.idea-fields').css({
		'display': 'block',
		'top': top,
		'right': right
	});

	if (typeof jQuery(e)[0].item.field !== 'undefined') {
		var field = jQuery(e)[0].item.field;
		field_id = field['id'];
		jQuery('.idea-fields .control-layer-field.control-layer-'+field_id+ ' button').addClass('active');
		jQuery('.idea-fields .control-layer-field.control-layer-'+field_id+ ' button').html('<i class="fa fa-check-square-o"></i>');
	}
});

jQuery(document).on('unselect.item.design', function(event, e) {
	jQuery('.idea-fields').css('display', 'none');
});