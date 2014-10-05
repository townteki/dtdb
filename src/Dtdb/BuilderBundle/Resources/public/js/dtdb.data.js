if (typeof DTDB != "object")
	var DTDB = { 
		data_loaded: jQuery.Callbacks(), 
		api_url: {
			sets: 'http://dtdb.com/api/sets/',
			cards: 'http://dtdb.com/api/cards/'
		},
		locale: 'en'
	};
DTDB.data = {};
(function(data, $) {
	data.sets = {};
	data.cards = {};

	var sets_data = null;
	var cards_data = null;
	var is_modified = null;

	data.query = function() {
		data.initialize();
		data.promise_sets = $
				.ajax(DTDB.api_url.sets+"?jsonp=DTDB.data.parse_sets&_locale="
						+ DTDB.locale);
		data.promise_cards = $
				.ajax(DTDB.api_url.cards+"?jsonp=DTDB.data.parse_cards&_locale="
						+ DTDB.locale);
		$.when(data.promise_sets, data.promise_cards).done(data.initialize);
	}

	data.initialize = function() {
		if (is_modified === false)
			return;

		sets_data = sets_data
				|| JSON.parse(localStorage
						.getItem('sets_data_' + DTDB.locale));
		if(!sets_data) return;
		data.sets = TAFFY(sets_data);
		data.sets.sort("cyclenumber,number");

		cards_data = cards_data
				|| JSON
						.parse(localStorage
								.getItem('cards_data_' + DTDB.locale));
		if(!cards_data) return;
		data.cards = TAFFY(cards_data);
		data.cards.sort("code");
		
		DTDB.data_loaded.fire();
	}

	data.parse_sets = function(response) {
		if(typeof response === "undefined") return;
		var json = JSON.stringify(sets_data = response);
		is_modified = is_modified
				|| json != localStorage.getItem("sets_data_" + DTDB.locale);
		localStorage.setItem("sets_data_" + DTDB.locale, json);
	}

	data.parse_cards = function(response) {
		if(typeof response === "undefined") return;
		var json = JSON.stringify(cards_data = response);
		is_modified = is_modified
				|| json != localStorage.getItem("cards_data_" + DTDB.locale);
		localStorage.setItem("cards_data_" + DTDB.locale, json);
	}
	
	$(function() {
		data.query();
	})

})(DTDB.data, jQuery);


