if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.deck_gallery = {};
(function(deck_gallery, $) {
	var images = null;

	deck_gallery.update = function() {

		images = [ Outfit.imagesrc ];
		qtys = [ 1 ];
		DTDB.data.cards({
			indeck : {
				'gt' : 0
			},
			type_code : {
				'!is' : 'outfit'
			}
		}).order('type_code,title').each(function(record) {
			images.push(record.imagesrc);
			qtys.push(record.indeck);
		});
		for (var i = 0; i < images.length; i++) {
			var cell = $('<td><div><img src="' + images[i] + '"><div>'+qtys[i]+'</div></div></td>');
			$('#deck_gallery tr').append(cell.data('index', i));
		}
	}

})(DTDB.deck_gallery, jQuery);
