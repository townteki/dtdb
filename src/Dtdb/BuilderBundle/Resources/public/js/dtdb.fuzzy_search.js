if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.fuzzy_search = {};
(function(fuzzy_search, $) {
    
    // takes a card name and fuzzy-searches it in the card db
    // the input can include a qty indicator like 3x
    // returns an array of objects Card with an additional key "qty"
    fuzzy_search.lookup = function(input, max_results) {
        if(max_results == null) max_results = 5;
        var qty = null, name = input.replace(/\(.*\)/, '').replace(/\W+/g, ' ').replace(/\s+/, ' ').trim().toLowerCase();
    	if(name.match(/^(\d+)x?\s*(.*)/)) {
    		qty = parseInt(RegExp.$1, 10);
    		name = RegExp.$2;
    	} else if(name.match(/(.*)\s*x?(\d+)$/)) {
    		qty = parseInt(RegExp.$2, 10);
    		name = RegExp.$1;
    	}
    	if(name == "") return;
    	var options = [];
    	var query = DTDB.data.cards({token: {likenocase:name}});
    	if(query.count()) {
    		query.each(function (record,recordnumber) {
    			options.push(record);
    		});
    	} else if(typeof String.prototype.score === "function") {
    		var matches = [];
    		$.each(dict, function(index, row) {
    			var score = row.token.score(name, 0.9);
                row.score = score;
    			matches.push(row);
    		});
    		matches.sort(function (a,b) { return a.score > b.score ? -1 : a.score < b.score ? 1 : 0 });
    		var bestScore = matches[0].score;
    		for(var i=0; i<max_results && matches[i].score > 0.4 && matches[i].score > bestScore * 0.9; i++) {
    			options.push(matches[i]);
    		}
    	}
        return { qty: qty, cards: options };
    }
    
    var dict = [];
    DTDB.data_loaded.add(function() {
    	DTDB.data.cards({pack_code:"alt"}).remove();
    	DTDB.data.cards().each(function (record, recordnumber) {
            record.token = record.title.replace(/\W+/, ' ').trim().toLowerCase();
    		dict.push(record);
    	});
    });
	
})(DTDB.fuzzy_search, jQuery);
