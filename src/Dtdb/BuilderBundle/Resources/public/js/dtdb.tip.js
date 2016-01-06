if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.tip = {};
(function(tip, $) {
	
	tip.display = function(event) {
		var code = $(this).data('index')
				|| $(this).closest('.card-container').data('index')
				|| ($(this).attr('href') && $(this).attr('href').replace(
						/.*\/card\/(\d\d\d\d\d).*$/,
						"$1"));
		code = String(code);
		if (!code)
			return;
		var card = DTDB.data.cards({
			code : code
		}).first();
		if (!card)
			return;
		var type = '<p class="card-info">' + DTDB.format.type(card) + '</p>';
		var keywords ='<div class="card-keywords">'+DTDB.format.keywords(card)+'</div>';
		if (card.strength != null)
			type += '<p>Strength <b>' + card.strength + '</b></p>';
		var gang_img = card.gang_code == "neutral" ? "" : '<img src="'
			+ Url_GangImage.replace('xxx', card.gang_code)
			+ '.png"> &bull; ';
		var image_svg = ''; 
		//if($('#dtdb_svg_hex').length && typeof InstallTrigger === 'undefined') {
			// no hexagon for Firefox, bad boy!
			//image_svg = '<div class="card-image"'+(card.imagesrc ? ' style="background-image:url('+card.imagesrc+')"': '')
			//+ '><svg width="103px" height="90px" viewBox="0 0 677 601" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><mask id="mask"><use xlink:href="#rect" style="fill:white" /><use xlink:href="#hex" style="fill:black"/></mask><use xlink:href="#rect" mask="url(#mask)"/><use xlink:href="#hex" style="stroke:black;fill:none;stroke-width:15" /></svg></div>';
		//}
		if (card.imagesrc){
			image_svg = '<div class="col-sm-4 pull-left"><img src="'+card.imagesrc+'" class="img-responsive"></div><div class="col-sm-8">'			
		} else {
			image_svg = '<div class="col-sm-12">'
		}
		$(this).qtip(
				{
					content : {
						text : '<div class="row">'
							    + image_svg
								+ '<h4>'
								+ (card.uniqueness ? "&diams; " : "")
								+ card.title + '</h4>' + type
								+ keywords
								+ '<div class="card-text ">' + DTDB.format.text(card) + '</div>'
								+ '<p class="card-gang" style="clear:right"><span class="pull-right">' + gang_img + card.pack + '</span>'
								+ DTDB.format.face(card)
								+ '</p></div></div>'
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

