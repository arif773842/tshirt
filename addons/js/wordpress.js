design.item.updateSizes = function(){
	design.item.unselect();
	jQuery('.labView.active .drag-item').each(function(){
		var e 	= jQuery(this);
		
		var text 	= e.find('text');
		if(typeof text[0] != 'undefined')
		{
			var width 	= e.outerWidth();
			var size 	= text[0].getBoundingClientRect();
			var change_size = width - size.width;
			if(change_size > 3)
			{
				var height 		= e.outerHeight();
				var position 	= e.position();

				var svg 	= e.find('svg');
				var viewBox = svg[0].getAttributeNS(null, 'viewBox');
				var options = viewBox.split(' ');
				var view_w 	= (size.width * options[2])/width;
				var view_h 	= (size.height * options[3])/height;

				var new_viewbox = options[0] +' '+ options[1] +' '+ view_w +' '+ view_h;
				svg[0].setAttributeNS(null, 'viewBox', new_viewbox);
				design.item.setSize(this, size.width, size.height);
			}
		}
	});
}