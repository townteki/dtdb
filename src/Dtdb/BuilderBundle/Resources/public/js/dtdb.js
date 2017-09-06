if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

function display_notification()
{
	if(!localStorage) return;
	var Notification = {
		version: 17,
		type: 'info',
		message: '<a href="https://www.kickstarter.com/projects/545820095/doomtown-reloaded-there-comes-a-reckoning">New Expansion There Comes a Reckoning is Live on Kickstarter!</a>'};
    var localStorageNotification = parseInt(localStorage.getItem('notification'));
    if(localStorageNotification >= Notification.version) return;
	var alert = $('<div class="alert alert-'+Notification.type+'"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'+Notification.message+'</div>');
	alert.bind('closed.bs.alert', function () {
		localStorage.setItem('notification', Notification.version);
	});
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
                  }, {
                      id: 'joker',
                      label: 'Jokers',
                      icon: ''
                  }
                ]
            ],
            'suit': [
                     [ // first column

                         {
                             id: 'Spades',
                             label: 'Spades <small>(* starting posse)</small> ',
                             icon: 'spades'
                         }, {
                             id: 'Clubs',
                             label: 'Clubs',
                             icon: 'clubs'
                         }                ],
                     [ // second column
                       {
                           id: 'Diams',
                           label: 'Diamonds',
                           icon: 'diams'
                       }, {
                           id: 'Hearts',
                           label: 'Hearts',
                           icon: 'hearts'
                       }, {
                           id: 'Joker',
                           label: 'Jokers',
                           icon: ''
                       }
                     ]
                 ],
            'number': [],
            'title': [
                [{
                    id: 'cards',
                    label: 'Cards'
                }]
            ],
            'gang': [
                [{
                    id: 'eaglewardens',
                    label: 'Eagle Wardens',
                    icon: ''
                },{
                    id: 'fourthring',
                    label: 'The Fourth Ring',
                    icon: ''
                },{
                    id: 'lawdogs',
                    label: 'Law Dogs',
                    icon: ''
                },{
                    id: 'morganco',
                    label: 'Morgan Cattle Co.',
                    icon: ''
                },{
                    id: 'righteousbandits',
                    label: 'The 108 Righteous Bandits',
                    icon: ''
                },{
                    id: 'sloanegang',
                    label: 'The Sloane Gang',
                    icon: ''
                },{
                    id: 'drifters',
                    label: 'Drifters',
                    icon: ''
                }, {
                    id: 'Joker',
                    label: 'Jokers',
                    icon: ''
                }],[{
                    id: 'neutral',
                    label: 'Neutral',
                    icon: ''
                }]
            ],
            'rank': [
                [{
                    id: '1',
                    label: 'A'
                },{
                    id: '2',
                    label: '2'
                },{
                    id: '3',
                    label: '3'
                },{
                    id: '4',
                    label: '4'
                },{
                    id: '5',
                    label: '5'
                },{
                    id: '6',
                    label: '6'
                },{
                    id: '7',
                    label: '7'
                }],[{
                    id: '8',
                    label: '8'
                },{
                    id: '9',
                    label: '9'
                },{
                    id: '10',
                    label: '10'
                },{
                    id: '11',
                    label: 'J'
                },{
                    id: '12',
                    label: 'Q'
                },{
                    id: '13',
                    label: 'K'
                },{
                    id: 'Joker',
                    label: 'Jokers'
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

	Legend = DTDB.data.cards({indeck:{'gt':0},type_code:'legend'}).first();

	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).order("type,title").each(function(record) {
		var type = record.type_code, keywordss = record.keywords_code ? record.keywords_code.split(" - ") : [];

		var gang_code = '';
		if(record.gang != Outfit.gang) {
			gang_code = record.gang_code;
		}

		if(bytype[type] == null) bytype[type] = [];
		bytype[type].push({
			card: record,
			qty: record.indeck,
			gang: gang_code,
			start: record.start
		});
	});
	bytype.outfit = [{
		card: Outfit,
		qty: 1,
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

	Legend = DTDB.data.cards({indeck:{'gt':0},type_code:'legend'}).first();

	for(var i=0; i<4; i++) {
		DeckDistribution[i] = [];
		for(var j=1; j<14; j++) {
			DeckDistribution[i][j] = 0;
		}
	}

	var displayDescription = getDisplayDescriptions(DisplaySort);
	if(displayDescription == null) return;

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
			var item = $('<h4> '+row.label+' (<span></span>)</h4>').hide();
			if(row.icon) {
				$('<span>').addClass(DisplaySort+'-icon').html('&'+row.icon+';').prependTo(item);
			} else if(DisplaySort == "gang") {
				var imgsrc = (row.id === "neutral" || row.id === "drifters" || row.id === "Joker") ? "" : '<img src="'
					+ Url_GangImage.replace('xxx', row.id)
					+ '.png">';
				$('<span>').addClass(DisplaySort+'-icon').html(imgsrc).prependTo(item);
			}
			var content = $('<div class="deck-'+row.id+'"></div>');
			div.append(item).append(content);
		}
	}

	var cabinet = {};
	DudeIndeck = [];
	DudeStarter = [];
	$('#outfit').html('<a href="'+Routing.generate('cards_zoom', {card_code:Outfit.code})+'" data-target="#cardModal" data-remote="false" class="card" data-toggle="modal" data-index="'+Outfit.code+'">'+Outfit.title+'</a>');
	$('#img_outfit').prop('src', Outfit.imagesrc);
	if(Legend)
	{
		$('#legend').html('<a href="'+Routing.generate('cards_zoom', {card_code:Legend.code})+'" data-target="#cardModal" data-remote="false" class="card" data-toggle="modal" data-index="'+Legend.code+'">'+Legend.title+'</a>');
		$('#img_legend').prop('src', Legend.imagesrc);
	}
	else {
		$('#legend').html('');
		$('#img_legend').prop('src', '');
	}


	var latestpack = DTDB.data.sets({name:Outfit.pack}).first();
	if(DisplaySort === 'type') {
		var preSort = 'value,title';
	} else if(DisplaySort === 'gang') {
		var preSort = 'type,value';
	} else if(DisplaySort === 'suit') {
		var preSort = 'value,title';
	} else if(DisplaySort === 'number') {
		var preSort = 'code';
	} else if(DisplaySort === 'title') {
		var preSort = 'title';
	} else if(DisplaySort === 'rank') {
		var preSort = 'suit,title';
	}
	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).order(preSort).each(function(record) {
		var pack = DTDB.data.sets({name:record.pack}).first();
		if(latestpack.cyclenumber < pack.cyclenumber || (latestpack.cyclenumber == pack.cyclenumber && latestpack.number < pack.number)) latestpack = pack;

		var criteria = null;
		var additional_info = '';

		if(DisplaySort === 'type') {
			criteria = record.type_code;
		} else if(DisplaySort === 'gang') {
			criteria = record.gang_code;
			if(record.gang_code === 'neutral' && record.type === "Dude") criteria = 'drifters'
			if(record.type_code == 'joker') criteria = 'Joker';
		} else if(DisplaySort === 'suit') {
			criteria = record.suit;
			if(record.type_code == 'joker') criteria = 'Joker';
		} else if(DisplaySort === 'number') {
			criteria = record.pack_code;
			var number_of_sets = Math.ceil(record.indeck / record.quantity);
			var alert_number_of_sets = number_of_sets > 1 ? '<small class="text-warning">'+number_of_sets+' sets needed</small> ' : '';
			additional_info = '(#' + record.number + ') ' + alert_number_of_sets;
		} else if(DisplaySort === 'title') {
			criteria = 'cards';
		} else if(DisplaySort === 'rank') {
			criteria = record.rank;
			if(record.type_code == 'joker') criteria = 'Joker';
		}

		var face = DTDB.format.face(record);
		var item = $('<div>'+record.indeck+'x '+face+' <a href="'+Routing.generate('cards_zoom', {card_code:record.code})+'" class="card'+(record.start ? ' card-start'+record.start : '')+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-index="'+record.code+'">'+record.title+'</a> '+additional_info+'</div>');
		item.appendTo($('#deck-content .deck-'+criteria));

		cabinet[criteria] |= 0;
		cabinet[criteria] = cabinet[criteria] + record.indeck;
		$('#deck-content .deck-'+criteria).prev().show().find('span:last').html(cabinet[criteria]);

		if(record.suit) {
			DeckDistribution[SuitNumbers[record.suit]][record.rank] += record.indeck;
		}

		if(record.type === "Dude"){
			var DudeLegalName = record.title;
			DudeLegalName = DudeLegalName.replace(/ \(Exp.\d\)/,"");
			if(DudeIndeck[DudeLegalName] == null){
				DudeIndeck[DudeLegalName] = record.indeck;
			} else {
				DudeIndeck[DudeLegalName] += record.indeck;
			}
			if(record.start){
				if(DudeStarter[DudeLegalName] == null){
					DudeStarter[DudeLegalName] = true;
				} else {
					DudeStarter[DudeLegalName] = false;
				}
			}
		}
	});
	$('#latestpack').html('Cards up to <i>'+latestpack.name+'</i>');
	check_composition();
	check_distribution();
	$('#deck').show();
}


function check_composition() {
	var startingposse = DTDB.data.cards({indeck:{'gt':0},start:{'gt':0}});
	var outfits = DTDB.data.cards({indeck:{'gt':0},type_code:{'is':'outfit'}});
	var legends = DTDB.data.cards({indeck:{'gt':0},type_code:{'is':'legend'}});
	var number_of_outfits = outfits.select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	var number_of_legends = legends.select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	var number_of_jokers = DTDB.data.cards({indeck:{'gt':0},type_code:{'is':'joker'}}).select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	var number_of_others = DTDB.data.cards({indeck:{'gt':0},suit:{'!is':null}}).select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);

	if($('#cardcount').size()) {
		$('#cardcount').html(number_of_others+" cards with printed value (required 52)")[number_of_others !=  52 ? 'addClass' : 'removeClass']("text-danger");
	}

	var legal = true;
	for(DudeLegalName in DudeIndeck){
		if (DudeIndeck[DudeLegalName] > 4){
			legal = false;
			break;
		}
	}
	if(number_of_outfits != 1) {
		$('#deckcomposition').html("Must have exactly one outfit.").addClass('text-danger');
	} else if(number_of_legends > 1) {
		$('#deckcomposition').html("Must have no more than 1 legend.").addClass('text-danger');
	} else if(number_of_jokers > 2) {
		$('#deckcomposition').html("Must have no more than 2 jokers.").addClass('text-danger');
	} else if(!legal) {
		$('#deckcomposition').html("Too many dudes with same name: "+DudeLegalName).addClass('text-danger');
	} else {
		$('#deckcomposition').empty();
	}

	if($('#startingnumbers').size()) {
		var outfit = outfits.first();
		var legend = legends.first();

		var start_wealth = outfit.wealth;
		var start_production = outfit.production;

		if(legend) {
			start_wealth += legend.wealth;
			start_production += legend.production;
		}


		var illegalStartingDude = false;

		var cost_of_starting_posse = 0;
		var upkeep_of_starting_posse = 0;
		var production_of_starting_posse = 0;
		var influence_of_starting_posse = 0;
		var size_of_starting_posse = 0;
		startingposse.each(function(record){

			cost_of_starting_posse += record.start * record.cost;
			// reducing cost by one for grifters in Den of Thieves
			if(outfit.code=="10003" && (record.keywords != null && record.keywords.search('Grifter') != -1) ){
				cost_of_starting_posse -= record.start;
			}
			upkeep_of_starting_posse += record.start * record.upkeep;
			production_of_starting_posse += record.start * record.production;
			influence_of_starting_posse += record.start * record.influence;
			size_of_starting_posse += record.start;

			if(record.gang_code !="neutral" && record.gang_code != outfit.gang_code) illegalStartingDude=true;
		});


		legal = true;
		for(DudeLegalName in DudeStarter){
			if (DudeStarter[DudeLegalName] == false){
				legal = false;
				break;
			}
		}
		if( start_wealth < cost_of_starting_posse){
			$('#startingcomposition').html("Negative starting Ghost Rock.").addClass('text-danger');
		} else if(!legal){
			$('#startingcomposition').html("More than one " + DudeLegalName + " starting.").addClass('text-danger');
		} else if(illegalStartingDude){
			$('#startingcomposition').html("Starting a dude from another outfit.").addClass('text-danger');
		} else {
			$('#startingcomposition').empty();
		}

		$('#startingnumbers').html('Starting with '+(start_wealth-cost_of_starting_posse)+' wealth, '+(start_production+production_of_starting_posse-upkeep_of_starting_posse)+' income and '+influence_of_starting_posse+' influence');
	}

}
function check_distribution() {
	var legal = true;
	for(var i=0; i<4; i++) {
		for(var j=1; j<14; j++) {
			if(DeckDistribution[i][j] > 4) {
				legal = false;
				break;
			}
		}
		if(!legal) break;
	}
	if(!legal) {
		$('#deckdistribution').html("Too many cards with same value: "+RankNames[j-1]+" of "+SuitNames[i]).addClass('text-danger');
	} else {
		$('#deckdistribution').empty();
	}
}
var DeckDistribution = [];
var DudeIndeck = [];
var DudeStarter = [];
var SuitNumbers = { Spades: 0, Diams: 1, Hearts: 2, Clubs: 3 };
var SuitNames = [ 'Spades', 'Diams', 'Hearts', 'Clubs' ];
var RankNames = ['A','2','3','4','5','6','7','8','9','10','J','Q','K'];
$(function () {

	if(Modernizr.touch) {
		$('#svg').remove();
		$('form.external').removeAttr('target');
	}

	display_notification();

	$.each([ 'table-predecessor', 'table-successor', 'table-draw-simulator', 'table-suggestions' ], function (i, table_id) {
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

var types = ["outfit", "dude", "deed", "goods", "spell", "action", "legend"];
var typesstr = ["Outfit", "Dude", "Deed", "Goods", "Spell", "Action", "Legend"];

function build_bbcode() {
	var deck = process_deck_by_type(SelectedDeck);
	var lines = [];
	lines.push("[b]"+SelectedDeck.name+"[/b]");
	lines.push("");
	$.each(types, function (n, type) {
		if(deck[type] != null) {
			if(type == "outfit") {
				var slot = deck[type][0];
				lines.push('[url=http://dtdb.co/'+DTDB.locale+'/card/'
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
	            	var start ="";
	            	for(var loop=slot.start; loop>0; loop--) start+="*";
					lines.push(slot.qty + 'x [url=http://dtdb.co/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ']'
					 + slot.card.title
					 + '[/url]'
					 + start
					 + ' [i]('
					 + slot.card.pack
					 + ")[/i]"
					);
				});
				lines.push("");
			}
		}
	});
	lines.push($('#cardcount').text());
	lines.push($('#latestpack').text());
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist [url="+location.href+"]published on DoomtownDB[/url].");
	} else {
		lines.push("Deck built on [url=http://dtdb.co]DoomtownDB[/url].");
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
	$.each(types, function (n, type) {
		if(deck[type] != null) {
			if(type == "outfit") {
				var slot = deck[type][0];
				lines.push('['
				 + slot.card.title
				 + '](http://dtdb.co/'+DTDB.locale+'/card/'
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
	            	var start ="";
	            	for(var loop=slot.start; loop>0; loop--) start+="*";
					lines.push('* '+ slot.qty + 'x ['
					 + slot.card.title
					 + '](http://dtdb.co/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ')'
					 + start
					 + '_('
					 + slot.card.pack
					 + ")_"
					);
				});

			}
		}
	});
	lines.push("");
	lines.push($('#cardcount').text() + "  ");
	lines.push($('#latestpack').text() + "  ");
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist [published on DoomtownDB]("+location.href+").");
	} else {
		lines.push("Deck built on [DoomtownDB](http://dtdb.co).");
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
	            	var start ="";
	            	for(var loop=slot.start; loop>0; loop--) start+="*";
					lines.push(slot.qty + 'x '
					 + slot.card.title
					 + start
					 + ' ('
					 + slot.card.pack
					 + ")"
					);
				});

			}
		}
	});
	lines.push("");
	lines.push($('#cardcount').text());
	lines.push($('#latestpack').text());
	lines.push("");
	if(typeof Decklist != "undefined" && Decklist != null) {
		lines.push("Decklist published on http://dtdb.co.");
	} else {
		lines.push("Deck built on http://dtdb.co.");
	}
	return lines;
}

function export_plaintext() {
	$('#export-deck').html(build_plaintext().join("\n"));
	$('#exportModal').modal('show');
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
        	k = n - k;
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
  this.$element  = $(element);
  this.options   = $.extend({}, Button.DEFAULTS, options);
  this.isLoading = false;
};

Button.DEFAULTS = {
  loadingText: 'loading...'
};

Button.prototype.setState = function (state) {
  var d    = 'disabled';
  var $el  = this.$element;
  var val  = $el.is('input') ? 'val' : 'html';
  var data = $el.data();

  state = state + 'Text';

  if (!data.resetText) $el.data('resetText', $el[val]());

  $el[val](data[state] || this.options[state]);

  // push to event loop to allow forms to submit
  setTimeout($.proxy(function () {
    if (state == 'loadingText') {
      this.isLoading = true;
      $el.addClass(d).attr(d, d);
    } else if (this.isLoading) {
      this.isLoading = false;
      $el.removeClass(d).removeAttr(d);
    }
  }, this), 0);
};

Button.prototype.toggle = function () {
  var changed = true;
  var $parent = this.$element.closest('[data-toggle="buttons"]');

  if ($parent.length) {
    var $input = this.$element.find('input');
    if ($input.prop('type') == 'radio') {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false;
      else $parent.find('.active').removeClass('active');
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change');
  }

  if (changed) this.$element.toggleClass('active');
};

Button.prototype.on = function () {
  var changed = true;
  var $parent = this.$element.closest('[data-toggle="buttons"]');

  if ($parent.length) {
    var $input = this.$element.find('input');
    if ($input.prop('type') == 'radio' || invertOthers) {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false;
      else $parent.find('.active').removeClass('active');
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change');
  }

  if (changed) this.$element.addClass('active');
};

Button.prototype.off = function () {
  var changed = true;
  var $parent = this.$element.closest('[data-toggle="buttons"]');

  if ($parent.length) {
    var $input = this.$element.find('input');
    if ($input.prop('type') == 'radio' || invertOthers) {
      if ($input.prop('checked') && this.$element.hasClass('active')) changed = false;
      else $parent.find('.active').removeClass('active');
    }
    if (changed) $input.prop('checked', !this.$element.hasClass('active')).trigger('change');
  }

  if (changed) this.$element.removeClass('active');
};


  // BUTTON PLUGIN DEFINITION
  // ========================

  var old = $.fn.button;

  $.fn.button = function (option, invertOthers) {
    return this.each(function () {
      var $this   = $(this);
      var data    = $this.data('bs.button');
      var options = typeof option == 'object' && option;

      if (!data) $this.data('bs.button', (data = new Button(this, options)));

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
    });
  };

  $.fn.button.Constructor = Button;


  // BUTTON NO CONFLICT
  // ==================

  $.fn.button.noConflict = function () {
    $.fn.button = old;
    return this;
  };

})(window.jQuery);
