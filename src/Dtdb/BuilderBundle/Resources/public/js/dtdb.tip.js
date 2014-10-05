if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.tip = {};
(function(tip, $) {
	
	tip.display = function(event) {
		var code = $(this).data('index')
				|| $(this).closest('.card-container').data('index')
				|| ($(this).attr('href') && $(this).attr('href').replace(
						/.*\/card\/(\d\d\d\d\d)$/,
						"$1"));
		var card = DTDB.data.cards({
			code : code
		}).first();
		if (!card)
			return;
		var type = '<p class="card-info">' + DTDB.format.type(card) + '</p>';
		if (card.strength != null)
			type += '<p>Strength <b>' + card.strength + '</b></p>';
		var image_svg = ''; 
		if($('#dtdb_svg_hex').length && typeof InstallTrigger === 'undefined') {
			// no hexagon for Firefox, bad boy!
			image_svg = '<div class="card-image"'+(card.imagesrc ? ' style="background-image:url('+card.imagesrc+')"': '')
			+ '><svg width="103px" height="90px" viewBox="0 0 677 601" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><mask id="mask"><use xlink:href="#rect" style="fill:white" /><use xlink:href="#hex" style="fill:black"/></mask><use xlink:href="#rect" mask="url(#mask)"/><use xlink:href="#hex" style="stroke:black;fill:none;stroke-width:15" /></svg></div>';
		}
		$(this).qtip(
				{
					content : {
						text : image_svg
								+ '<h4>'
								+ (card.uniqueness ? "&diams; " : "")
								+ card.title + '</h4>' + type
								+ '<div class="card-text">' + DTDB.format.text(card) + '</div>'
								+ '<p class="card-gang" style="clear:right"><span class="pull-right">' + card.gang + ' &ndash; ' + card.pack + '</span>'
								+ DTDB.format.value(card)
								+ '</p>'
					},
					style : {
						classes : 'qtip-bootstrap qtip-dtdb'
					},
					position : {
						my : 'left center',
						at : 'right center',
						viewport : $(window)
					},
					show : {
						event : event.type,
						ready : true,
						solo : true
					}/*,
					hide : {
						event: 'unfocus'
					}*/
				}, event);
	};

	$(function() {

		if(typeof Modernizr == 'undefined' || !Modernizr.touch ) {
			$('body').on({
				mouseover : tip.display,
				focus : tip.display
			}, 'a');
		}

	});

})(DTDB.tip, jQuery);

