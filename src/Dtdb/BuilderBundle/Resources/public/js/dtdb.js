if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

function display_notification()
{
	if(!localStorage) return;
	var Notification = {
		version: '1.0.0',
		message: ""
	};
	if(!Notification.message) return;
	var localStorageNotification = localStorage.getItem('notification');
	if(localStorageNotification === Notification.version) return;
	var alert = $('<div class="alert alert-warning"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+Notification.message+'</div>');
	alert.bind('closed.bs.alert', function () {
		localStorage.setItem('notification', Notification.version);  
	})
	$('#wrapper>div.container').prepend(alert);
}



function debounce(fn, delay) {
	var timer = null;
	return function() {
		var context = this, args = arguments;
		clearTimeout(timer);
		timer = setTimeout(function() {
			fn.apply(context, args);
		}, delay);
	};
}

function getDisplayDescriptions(sort) {
        var dd = {
            'type': [
                [ // first column

                    {
                        id: 'dude',
                        label: 'Dudes',
                        icon: 'spades'
                    }, {
                        id: 'action',
                        label: 'Actions',
                        icon: 'clubs'
                    }                ],
                [ // second column
                  {
                      id: 'deed',
                      label: 'Deeds',
                      icon: 'diams'
                  }, {
                      id: 'goods',
                      label: 'Goods',
                      icon: 'hearts'
                  }, {
                      id: 'spell',
                      label: 'Spells',
                      icon: 'hearts'
                  }
                ]
            ],
            'number': [],
            'title': [
                [{
                    id: 'cards',
                    label: 'Cards'
                }]
            ]
        };
        return dd[sort];
}


function process_deck_by_type() {
	
	var bytype = {};
	Outfit = DTDB.data.cards({indeck:{'gt':0},type_code:'outfit'}).first();
	if(!Outfit) {
		return;
	}

	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).order("type,title").each(function(record) {
		var type = record.type_code, keywordss = record.keywords_code ? record.keywords_code.split(" - ") : [];
		if(type == "ice") {
			var ice_type = [];
			 if(keywordss.indexOf("barrier") >= 0) {
				 ice_type.push("barrier");
			 }
			 if(keywordss.indexOf("code gate") >= 0) {
				 ice_type.push("code-gate");
			 }
			 if(keywordss.indexOf("sentry") >= 0) {
				 ice_type.push("sentry");
			 }
			 switch(ice_type.length) {
			 case 0: type = "none"; break;
			 case 1: type = ice_type.pop(); break;
			 default: type = "multi"; break;
			 }
		}
		if(type == "program") {
			 if(keywordss.indexOf("icebreaker") >= 0) {
				 type = "icebreaker";
			 }
		}
		var influence = 0, gang_code = '';
		if(record.gang != Outfit.gang) {
			gang_code = record.gang_code;
			influence = record.gangcost * record.indeck;
		}
		
		if(bytype[type] == null) bytype[type] = [];
		bytype[type].push({
			card: record,
			qty: record.indeck,
			influence: influence,
			gang: gang_code
		});
	});
	bytype.outfit = [{
		card: Outfit,
		qty: 1,
		influence: 0,
		gang: ''
	}];
	
	return bytype;
}

function update_deck(options) {
	var restrainOneColumn = false;
	if(options) {
		if(options.restrainOneColumn) restrainOneColumn = options.restrainOneColumn;
	}
	
	Outfit = DTDB.data.cards({indeck:{'gt':0},type_code:'outfit'}).first();
	if(!Outfit) return;

	var displayDescription = getDisplayDescriptions(DisplaySort);
	if(displayDescription == null) return;
	
	if(DisplaySort === 'gang') {
		for(var i=0; i<displayDescription[1].length; i++) {
			if(displayDescription[1][i].id === Outfit.gang_code) {
				displayDescription[0] = displayDescription[1].splice(i, 1);
				break;
			}
		}
	}
	if(DisplaySort === 'number' && displayDescription.length === 0) {
		var rows = [];
		DTDB.data.sets().each(function (record) {
			rows.push({id: record.code, label: record.name});
		});
		displayDescription.push(rows);
	}
	if(restrainOneColumn && displayDescription.length == 2) {
		displayDescription = [ displayDescription[0].concat(displayDescription[1]) ];
	}
	
	$('#deck-content').empty();
	var cols_size = 12/displayDescription.length;
	for(var colnum=0; colnum<displayDescription.length; colnum++) {
		var rows = displayDescription[colnum];
		var div = $('<div>').addClass('col-sm-'+cols_size).appendTo($('#deck-content'));
		for(var rownum=0; rownum<rows.length; rownum++) {
			var row = rows[rownum];
			var item = $('<h5> '+row.label+' (<span></span>)</h5>').hide();
			if(row.icon) {
				$('<span>').addClass(DisplaySort+'-icon').html('&'+row.icon+';').prependTo(item);
			} else if(DisplaySort == "gang") {
				$('<span class="icon icon-'+row.id+' '+row.id+'"></span>').prependTo(item);
			}
			var content = $('<div class="deck-'+row.id+'"></div>')
			div.append(item).append(content);
		}
	}
	
	InfluenceLimit = 0;
	var cabinet = {};
	$('#outfit').html('<a href="'+Routing.generate('cards_zoom', {card_code:Outfit.code})+'" data-target="#cardModal" data-remote="false" class="card" data-toggle="modal" data-index="'+Outfit.code+'">'+Outfit.title+'</a>');
	$('#img_outfit').prop('src', Outfit.imagesrc);
	InfluenceLimit = Outfit.influencelimit;
	if(typeof InfluenceLimit === "undefined") InfluenceLimit = Number.POSITIVE_INFINITY;
	MinimumDeckSize = Outfit.minimumdecksize;

	var latestpack = DTDB.data.sets({name:Outfit.pack}).first();
	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).order(DisplaySort === 'number' ? 'code' : 'title').each(function(record) {
		var pack = DTDB.data.sets({name:record.pack}).first();
		if(latestpack.cyclenumber < pack.cyclenumber || (latestpack.cyclenumber == pack.cyclenumber && latestpack.number < pack.number)) latestpack = pack;
		
		var influence = '';
		if(record.gang != Outfit.gang) {
			var infcost = record.gangcost * record.indeck;
			for(var i=0; i<infcost; i++) {
				if(i%5 == 0) influence+=" ";
				influence+="&bull;";
			}
			influence = ' <span class="influence-'+record.gang_code+'">'+influence+'</span>';
		}

		var criteria = null;
		var additional_info = influence;
		
		if(DisplaySort === 'type') {
			criteria = record.type_code, keywordss = record.keywords_code ? record.keywords_code.split(" - ") : [];
			if(criteria == "ice") {
				var ice_type = [];
				if(keywordss.indexOf("barrier") >= 0) ice_type.push("barrier");
				if(keywordss.indexOf("code gate") >= 0) ice_type.push("code-gate");
				if(keywordss.indexOf("sentry") >= 0) ice_type.push("sentry");
				switch(ice_type.length) {
				case 0: criteria = "none"; break;
				case 1: criteria = ice_type.pop(); break;
				default: criteria = "multi"; break;
				}
			}
			if(criteria == "program") {
				 if(keywordss.indexOf("icebreaker") >= 0) criteria = "icebreaker";
			}
		} else if(DisplaySort === 'gang') {
			criteria = record.gang_code;
		} else if(DisplaySort === 'number') {
			criteria = record.pack_code;
			var number_of_sets = Math.ceil(record.indeck / record.quantity);
			var alert_number_of_sets = number_of_sets > 1 ? '<small class="text-warning">'+number_of_sets+' sets needed</small> ' : '';
			additional_info = '(#' + record.number + ') ' + alert_number_of_sets + influence;
		} else if(DisplaySort === 'title') {
			criteria = 'cards';
		}

		var item = $('<div>'+record.indeck+'x <a href="'+Routing.generate('cards_zoom', {card_code:record.code})+'" class="card" data-toggle="modal" data-remote="false" data-target="#cardModal" data-index="'+record.code+'">'+record.title+'</a> '+additional_info+'</div>');
		item.appendTo($('#deck-content .deck-'+criteria));
		
		cabinet[criteria] |= 0;
		cabinet[criteria] = cabinet[criteria] + record.indeck;
		$('#deck-content .deck-'+criteria).prev().show().find('span:last').html(cabinet[criteria]);
		
	});
	$('#latestpack').html('Cards up to <i>'+latestpack.name+'</i>');
	check_influence();
	check_decksize();
	if($('#costChart .highcharts-container').size()) setTimeout(make_cost_graph, 100);
	if($('#strengthChart .highcharts-container').size()) setTimeout(make_strength_graph, 100);
	$('#deck').show();
}


function check_decksize() {
	DeckSize = DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	MinimumDeckSize = Outfit.minimumdecksize;
	$('#cardcount').html(DeckSize+" cards (min "+MinimumDeckSize+")")[DeckSize < MinimumDeckSize ? 'addClass' : 'removeClass']("text-danger");
}

function check_influence() {
	InfluenceSpent = 0;
	var repartition_influence = {};
	DTDB.data.cards({indeck:{'gt':0},gang_code:{'!is':Outfit.gang_code}}).each(function(record) {
		if(record.gangcost) {
			var inf, gang = record.gang_code;
			if(Outfit.code == "03029" && record.type_code == "program") {
				inf = record.indeck > 1 ? (record.indeck-1) * record.gangcost : 0;
			} else {
				inf = record.indeck * record.gangcost;
			}
			if(inf) {
				InfluenceSpent += inf;
				repartition_influence[gang] = (repartition_influence[gang] || 0) + inf;
			}
		}
	});
	var graph = '', displayInfluenceLimit = InfluenceLimit;
	if(InfluenceLimit !== Number.POSITIVE_INFINITY) {
		$.each(repartition_influence, function (key, value) {
			var ronds = '';
			for(var i=0; i<value; i++) {
				ronds += '&bull;';
			}
			graph += '<span class="influence-'+key+'" title="'+key+': '+value+'">'+ronds+'</span>';
		})
	} else {
		displayInfluenceLimit = "&#8734;";
	}
}

$(function () {
	
	if(Modernizr.touch) {
		$('#svg').remove();
		$('form.external').removeAttr('target');
	}
		
	display_notification();
	
	$.each([ 'table-graph-costs', 'table-graph-strengths', 'table-predecessor', 'table-successor', 'table-draw-simulator', 'table-suggestions' ], function (i, table_id) {
		var table = $('#'+table_id);
		if(!table.size()) return;
		var head = table.find('thead tr th');
		var toggle = $('<a href="#" class="pull-right small">hide</a>');
		toggle.on({click: toggle_table});
		head.prepend(toggle);
	});
	
	$('#oddsModal').on({change: oddsModalCalculator}, 'input');
});

function oddsModalCalculator(event) {
	var inputs = {};
	$.each(['N','K','n','k'], function (i, key) {
		inputs[key] = parseInt($('#odds-calculator-'+key).val(), 10) || 0;
	});
	$('#odds-calculator-p').text( Math.round( 100 * hypergeometric.get_cumul(inputs.k, inputs.N, inputs.K, inputs.n) ) );
}

function toggle_table(event) {
	event.preventDefault();
	var toggle = $(this);
	var table = toggle.closest('table');
	var tbody = table.find('tbody');
	tbody.toggle(400, function() { toggle.text(tbody.is(':visible') ? 'hide': 'show'); });
}

var GangColors = {
	"anarch": "#FF4500",
	"criminal": "#4169E1",
	"shaper": "#32CD32",
	"neutral": "#708090",
	"haas-bioroid": "#8A2BE2",
	"jinteki": "#DC143C",
	"nbn": "#FF8C00",
	"weyland-consortium": "#006400"
};

function build_bbcode() {
	var deck = process_deck_by_type(SelectedDeck);
	var lines = [];
	lines.push("[b]"+SelectedDeck.name+"[/b]");
	lines.push("");
	var types = ["outfit", "event", "hardware", "resource", "icebreaker", "program", "agenda", "asset", "upgrade", "operation", "barrier", "code-gate", "sentry", "none", "multi"];
	var typesstr = ["Outfit", "Event", "Hardware", "Resource", "Icebreaker", "Program", "Agenda", "Asset", "Upgrade", "Operation", "Barrier", "Code Gate", "Sentry", "Other", "Multi"];
	$.each(types, function (n, type) {
		if(deck[type] != null) {
			if(type == "outfit") {
				var slot = deck[type][0];
				lines.push('[url=http://dtdb.com/'+DTDB.locale+'/card/'
				 + slot.card.code
				 + ']'
				 + slot.card.title
				 + '[/url] ('
				 + slot.card.pack
				 + ")");
				 lines.push("");
			} else {
				var count = deck[type].reduce(function (prev, curr) { return prev + curr.qty; }, 0);
				lines.push("[b]"+typesstr[n]+"[/b] ("+count+")");
				$.each(deck[type], function (n, slot) {
					var inf = "";
					for(var i=0; i<slot.influence; i++) {
						if(i%5==0) inf += " ";
						inf+="•";
					}
					lines.push(slot.qty + 'x [url=http://dtdb.com/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ']'
					 + slot.card.title
					 + '[/url] [i]('
					 + slot.card.pack
					 + ")[/i]"
					 + ( slot.influence ? '[color=' + GangColors[slot.gang] + ']' + inf + '[/color]' : '' )
					);
				});
				lines.push("");
			}
		}
	});
	lines.push($('#influence').text().replace(/•/g,''));
	lines.push($('#cardcount').text());
	lines.push($('#latestpack').text());
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist [url="+location.href+"]published on NetrunnerDB[/url].");
	} else {
		lines.push("Deck built on [url=http://dtdb.com]NetrunnerDB[/url].");
	}
	return lines;
}

function export_bbcode() {
	$('#export-deck').html(build_bbcode().join("\n"));
	$('#exportModal').modal('show');
}

function build_markdown() {
	var deck = process_deck_by_type(SelectedDeck);
	var lines = [];
	lines.push("# "+SelectedDeck.name);
	lines.push("");
	var types = ["outfit", "event", "hardware", "resource", "icebreaker", "program", "agenda", "asset", "upgrade", "operation", "barrier", "code-gate", "sentry", "none", "multi"];
	var typesstr = ["Outfit", "Event", "Hardware", "Resource", "Icebreaker", "Program", "Agenda", "Asset", "Upgrade", "Operation", "Barrier", "Code Gate", "Sentry", "Other", "Multi"];
	$.each(types, function (n, type) {
		if(deck[type] != null) {
			if(type == "outfit") {
				var slot = deck[type][0];
				lines.push('['
				 + slot.card.title
				 + '](http://dtdb.com/'+DTDB.locale+'/card/'
				 + slot.card.code
				 + ') _('
				 + slot.card.pack
				 + ")_");
				 lines.push("");
			} else {
				var count = deck[type].reduce(function (prev, curr) { return prev + curr.qty; }, 0);
				lines.push("");
				lines.push("## "+typesstr[n]+" ("+count+")");
				lines.push("");
				$.each(deck[type], function (n, slot) {
					var inf = "";
					for(var i=0; i<slot.influence; i++) {
						if(i%5==0) inf += " ";
						inf+="•";
					}
					lines.push('* '+ slot.qty + 'x ['
					 + slot.card.title 
					 + '](http://dtdb.com/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ') _('
					 + slot.card.pack
					 + ")_"
					 + ( slot.influence ? inf : '' )
					);
				});
				
			}
		}
	});
	lines.push("");
	lines.push($('#influence').text().replace(/•/g,'') + "  ");
	lines.push($('#cardcount').text() + "  ");
	lines.push($('#latestpack').text() + "  ");
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist [published on NetrunnerDB]("+location.href+").");
	} else {
		lines.push("Deck built on [NetrunnerDB](http://dtdb.com).");
	}
	return lines;
}

function export_markdown() {
	$('#export-deck').html(build_markdown().join("\n"));
	$('#exportModal').modal('show');
}

function build_plaintext() {
	var deck = process_deck_by_type(SelectedDeck);
	var lines = [];
	lines.push(SelectedDeck.name);
	lines.push("");
	var types = ["outfit", "event", "hardware", "resource", "icebreaker", "program", "agenda", "asset", "upgrade", "operation", "barrier", "code-gate", "sentry", "none", "multi"];
	var typesstr = ["Outfit", "Event", "Hardware", "Resource", "Icebreaker", "Program", "Agenda", "Asset", "Upgrade", "Operation", "Barrier", "Code Gate", "Sentry", "Other", "Multi"];
	$.each(types, function (n, type) {
		if(deck[type] != null) {
			if(type == "outfit") {
				var slot = deck[type][0];
				lines.push(slot.card.title
				 + ' ('
				 + slot.card.pack
				 + ")");
			} else {
				var count = deck[type].reduce(function (prev, curr) { return prev + curr.qty; }, 0);
				lines.push("");
				lines.push(typesstr[n]+" ("+count+")");
				$.each(deck[type], function (n, slot) {
					var inf = "";
					for(var i=0; i<slot.influence; i++) {
						if(i%5==0) inf += " ";
						inf+="•";
					}
					lines.push(slot.qty + 'x '
					 + slot.card.title
					 + ' ('
					 + slot.card.pack
					 + ")"
					 + ( slot.influence ? inf : '' )
					);
				});
				
			}
		}
	});
	lines.push("");
	lines.push($('#influence').text().replace(/•/g,''));
	lines.push($('#cardcount').text());
	lines.push($('#latestpack').text());
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist published on http://dtdb.com.");
	} else {
		lines.push("Deck built on http://dtdb.com.");
	}
	return lines;
}

function export_plaintext() {
	$('#export-deck').html(build_plaintext().join("\n"));
	$('#exportModal').modal('show');
}

function make_cost_graph() {
	var costs = [];
	
	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).each(function(record) {
		if(record.cost != null) {
			if(costs[record.cost] == null) costs[record.cost] = [];
			if(costs[record.cost][record.type] == null) costs[record.cost][record.type] = 0;
			costs[record.cost][record.type] += record.indeck;
		}
	});
	
	// costChart
	var cost_series = [ { name: 'Event', data: [] }, { name: 'Resource', data: [] }, { name: 'Hardware', data: [] }, { name: 'Program', data: [] } ];
	var xAxis = [];
	
	for(var j=0; j<costs.length; j++) {
		xAxis.push(j);
		var data = costs[j];
		for(var i=0; i<cost_series.length; i++) {
			var type_name = cost_series[i].name;
			cost_series[i].data.push(data && data[type_name] ? data[type_name] : 0);
		}
	}
	
	$('#costChart').highcharts({
		colors: ['#FFE66F', '#316861', '#97BF63', '#5863CC' ],
		title: {
			text: null,
		},
		credits: {
			enabled: false,
		},
		chart: {
            type: 'column'
        },
        xAxis: {
            categories: xAxis,
        },
        yAxis: {
            title: {
                text: null
            },
            allowDecimals: false,
            minTickInterval: 1,
            minorTickInterval: 1,
            endOnTick: false
        },
        plotOptions: {
            column: {
                stacking: 'normal',
            }
        },
        series: cost_series
	});

}

function make_strength_graph() {
	var strengths = [];
	var ice_types = [ 'Barrier', 'Code Gate', 'Sentry', 'Other' ];
	
	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).each(function(record) {
		if(record.strength != null) {
			if(strengths[record.strength] == null) strengths[record.strength] = [];
			var ice_type = 'Other';
			for(var i=0; i<ice_types.length; i++) {
				if(record.keywords.indexOf(ice_types[i]) != -1) {
					ice_type = ice_types[i];
					break;
				}
			}
			if(strengths[record.strength][ice_type] == null) strengths[record.strength][ice_type] = 0;
			strengths[record.strength][ice_type] += record.indeck;
		}
	});
	
	// strengthChart
	var strength_series = [];
	for(var i=0; i<ice_types.length; i++) strength_series.push({ name: ice_types[i], data: [] });
	var xAxis = [];

	for(var j=0; j<strengths.length; j++) {
		xAxis.push(j);
		var data = strengths[j];
		for(var i=0; i<strength_series.length; i++) {
			var type_name = strength_series[i].name;
			strength_series[i].data.push(data && data[type_name] ? data[type_name] : 0);
		}
	}

	$('#strengthChart').highcharts({
		colors: ['#487BCC', '#B8EB59', '#FF6251', '#CCCCCC'],
		title: {
			text: null,
		},
		credits: {
			enabled: false,
		},
		chart: {
            type: 'column'
        },
        xAxis: {
            categories: xAxis
        },
        yAxis: {
            title: {
                text: null
            },
            allowDecimals: false,
            minTickInterval: 1,
            minorTickInterval: 1,
            endOnTick: false
        },
        plotOptions: {
            column: {
                stacking: 'normal',
            }
        },
        series: strength_series
	});
	
}

//binomial coefficient module, shamelessly ripped from https://github.com/pboyer/binomial.js
var binomial = {};
(function( binomial ) {
	var memo = [];
	binomial.get = function(n, k) {
		if (k === 0) {
			return 1;
		}
		if (n === 0 || k > n) {
			return 0;
		}
		if (k > n - k) {
        	k = n - k
        }
		if ( memo_exists(n,k) ) {
			return get_memo(n,k);
		}
	    var r = 1,
	    	n_o = n;
	    for (var d=1; d <= k; d++) {
	    	if ( memo_exists(n_o, d) ) {
	    		n--;
	    		r = get_memo(n_o, d);
	    		continue;
	    	}
			r *= n--;
	  		r /= d;
	  		memoize(n_o, d, r);
	    }
	    return r;
	};
	function memo_exists(n, k) {
		return ( memo[n] != undefined && memo[n][k] != undefined );
	};
	function get_memo(n, k) {
		return memo[n][k];
	};
	function memoize(n, k, val) {
		if ( memo[n] === undefined ) {
			memo[n] = [];
		}
		memo[n][k] = val;
	};
})(binomial);

// hypergeometric distribution module, homemade
var hypergeometric = {};
(function( hypergeometric ) {
	var memo = [];
	hypergeometric.get = function(k, N, K, n) {
		if ( !k || !N || !K || !n ) return 0;
		if ( memo_exists(k, N, K, n) ) {
			return get_memo(k, N, K, n);
		}
		if ( memo_exists(n - k, N, N - K, n) ) {
			return get_memo(n - k, N, N - K, n);
		}
		if ( memo_exists(K - k, N, K, N - n) ) {
			return get_memo(K - k, N, K, N - n);
		}
		if ( memo_exists(k, N, n, K) ) {
			return get_memo(k, N, n, K);
		}
		var d = binomial.get(N, n);
		if(d === 0) return 0;
		var r = binomial.get(K, k) * binomial.get(N - K, n - k) / d;
		memoize(k, N, K, n, r);
		return r;
	};
	hypergeometric.get_cumul = function(k, N, K, n) {
		var r = 0;
		for(; k <= n; k++) {
			r += hypergeometric.get(k, N, K, n);
		}
		return r;
	};
	function memo_exists(k, N, K, n) {
		return ( memo[k] != undefined && memo[k][N] != undefined && memo[k][N][K] != undefined && memo[k][N][K][n] != undefined );
	};
	function get_memo(k, N, K, n) {
		return memo[k][N][K][n];
	};
	function memoize(k, N, K, n, val) {
		if ( memo[k] === undefined ) {
			memo[k] = [];
		}
		if ( memo[k][N] === undefined ) {
			memo[k][N] = [];
		}
		if ( memo[k][N][K] === undefined ) {
			memo[k][N][K] = [];
		}
		memo[k][N][K][n] = val;
	};
})(hypergeometric);





/* my version of button.js, overriding twitter's */

(function ($) { "use strict";

  // BUTTON PUBLIC CLASS DEFINITION
  // ==============================

var Button = function (element, options) {
  this.$element  = $(element)
  this.options   = $.extend({}, Button.DEFAULTS, options)
  this.isLoading = false
}

Button.DEFAULTS = {
  loadingText: 'loading...'
}

Button.prototype.setState = function (state) {
  var d    = 'disabled'
  var $el  = this.$element
  var val  = $el.is('input') ? 'val' : 'html'
  var data = $el.data()

  state = state + 'Text'

  if (!data.resetText) $el.data('resetText', $el[val]())

  $el[val](data[state] || this.options[state])

  // push to event loop to allow forms to submit
  setTimeout($.proxy(function () {
    if (state == 'loadingText') {
      this.isLoading = true
      $el.addClass(d).attr(d, d)
    } else if (this.isLoading) {
      this.isLoading = false
      $el.removeClass(d).removeAttr(d)
    }
  }, this), 0)
}

Button.prototype.toggle = function () {
  var changed = true
  var $parent = this.$element.closest('[data-toggle="buttons"]')

  if ($parent.length) {
    var $input = this.$element.find('input')
    if ($input.prop('type') == 'radio') {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false
      else $parent.find('.active').removeClass('active')
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change')
  }

  if (changed) this.$element.toggleClass('active')
}

Button.prototype.on = function () {
  var changed = true
  var $parent = this.$element.closest('[data-toggle="buttons"]')

  if ($parent.length) {
    var $input = this.$element.find('input')
    if ($input.prop('type') == 'radio' || invertOthers) {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false
      else $parent.find('.active').removeClass('active')
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change')
  }

  if (changed) this.$element.addClass('active')
}

Button.prototype.off = function () {
  var changed = true
  var $parent = this.$element.closest('[data-toggle="buttons"]')

  if ($parent.length) {
    var $input = this.$element.find('input')
    if ($input.prop('type') == 'radio' || invertOthers) {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false
      else $parent.find('.active').removeClass('active')
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change')
  }

  if (changed) this.$element.removeClass('active')
}


  // BUTTON PLUGIN DEFINITION
  // ========================

  var old = $.fn.button

  $.fn.button = function (option, invertOthers) {
    return this.each(function () {
      var $this   = $(this)
      var data    = $this.data('bs.button')
      var options = typeof option == 'object' && option

      if (!data) $this.data('bs.button', (data = new Button(this, options)))

      switch(option) {
      	case 'toggle':
      		data.toggle();
      		break;
      	case 'off':
      		data.off(invertOthers);
      		break;
      	case 'on':
      		data.on(invertOthers);
      		break;
      	default:
      		data.setState(option);
      		break;
      }
    })
  }

  $.fn.button.Constructor = Button


  // BUTTON NO CONFLICT
  // ==================

  $.fn.button.noConflict = function () {
    $.fn.button = old
    return this
  }

})(window.jQuery);
