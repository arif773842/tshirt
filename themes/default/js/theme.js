// fixed new option style
jQuery(document).on('control.layers.setup', function(event, e) {
	if (jQuery('.layer-options').length) {
		var offset = jQuery(e).offset(),
			top = parseInt(offset.top) - parseInt(jQuery('.layer-options').height());
		if (top < 0) top = 0;

		jQuery('.layer-options').css('top', top+'px');
	}
});

jQuery(document).ready(function(){
	var col_right = jQuery('#dg-designer .col-right').height();
	var col_left = jQuery('#dg-designer .col-left').height();
	var col_center = jQuery('#dg-designer .col-center').height();

	if(col_right < col_left)
		col_right = col_left;
	if(col_center < col_right)
		col_center = col_right;
	if(col_center < 580)
		col_center = col_center + 50;

	jQuery('#design-area').css('min-height', col_center);
});