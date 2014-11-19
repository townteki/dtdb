if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

function display_notification()
{
	if(!localStorage) return;
	var Notification = {
			version: 2,
			type: 'info',
			message: "New section 'Reviews'."
	};
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
            'value': [
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
		var gang_code = '';
		if(record.gang != Outfit.gang) {
			gang_code = record.gang_code;
		}
		
		if(bytype[type] == null) bytype[type] = [];
		bytype[type].push({
			card: record,
			qty: record.indeck,
			gang: gang_code
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

	for(var i=0; i<4; i++) {
		DeckDistribution[i] = [];
		for(var j=1; j<14; j++) {
			DeckDistribution[i][j] = 0;
		}
	}
	
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
			var item = $('<h4> '+row.label+' (<span></span>)</h4>').hide();
			if(row.icon) {
				$('<span>').addClass(DisplaySort+'-icon').html('&'+row.icon+';').prependTo(item);
			} else if(DisplaySort == "gang") {
				$('<span class="icon icon-'+row.id+' '+row.id+'"></span>').prependTo(item);
			}
			var content = $('<div class="deck-'+row.id+'"></div>');
			div.append(item).append(content);
		}
	}
	
	var cabinet = {};
	$('#outfit').html('<a href="'+Routing.generate('cards_zoom', {card_code:Outfit.code})+'" data-target="#cardModal" data-remote="false" class="card" data-toggle="modal" data-index="'+Outfit.code+'">'+Outfit.title+'</a>');
	$('#img_outfit').prop('src', Outfit.imagesrc);

	var latestpack = DTDB.data.sets({name:Outfit.pack}).first();
	DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).order(DisplaySort === 'number' ? 'code' : 'value').each(function(record) {
		var pack = DTDB.data.sets({name:record.pack}).first();
		if(latestpack.cyclenumber < pack.cyclenumber || (latestpack.cyclenumber == pack.cyclenumber && latestpack.number < pack.number)) latestpack = pack;
		
		var criteria = null;
		var additional_info = '';
		
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
		} else if(DisplaySort === 'value') {
			criteria = record.suit;
			if(record.type_code == 'joker') criteria = 'Joker';
		} else if(DisplaySort === 'number') {
			criteria = record.pack_code;
			var number_of_sets = Math.ceil(record.indeck / record.quantity);
			var alert_number_of_sets = number_of_sets > 1 ? '<small class="text-warning">'+number_of_sets+' sets needed</small> ' : '';
			additional_info = '(#' + record.number + ') ' + alert_number_of_sets;
		} else if(DisplaySort === 'title') {
			criteria = 'cards';
		}

		var face = DTDB.format.face(record);
		var item = $('<div>'+record.indeck+'x '+face+' <a href="'+Routing.generate('cards_zoom', {card_code:record.code})+'" class="card'+(record.start ? ' card-start' : '')+'" data-toggle="modal" data-remote="false" data-target="#cardModal" data-index="'+record.code+'">'+record.title+'</a> '+additional_info+'</div>');
		item.appendTo($('#deck-content .deck-'+criteria));
		
		cabinet[criteria] |= 0;
		cabinet[criteria] = cabinet[criteria] + record.indeck;
		$('#deck-content .deck-'+criteria).prev().show().find('span:last').html(cabinet[criteria]);
		
		if(record.suit) {
			DeckDistribution[SuitNumbers[record.suit]][record.rank] += record.indeck;
		}
	});
	$('#latestpack').html('Cards up to <i>'+latestpack.name+'</i>');
	check_composition();
	check_distribution();
	$('#deck').show();
}


function check_composition() {
	var startingposse = DTDB.data.cards({'start':1});
	var outfits = DTDB.data.cards({indeck:{'gt':0},type_code:{'is':'outfit'}});
	var number_of_outfits = outfits.select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	var number_of_jokers = DTDB.data.cards({indeck:{'gt':0},type_code:{'is':'joker'}}).select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	var number_of_others = DTDB.data.cards({indeck:{'gt':0},suit:{'!is':null}}).select("indeck").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
	
	if($('#cardcount').size()) {
		$('#cardcount').html(number_of_others+" cards with printed value (required 52)")[number_of_others !=  52 ? 'addClass' : 'removeClass']("text-danger");
	}
	if(number_of_outfits != 1) {
		$('#deckcomposition').html("Must have exactly one outfit.").addClass('text-danger');
	} else if(number_of_jokers > 2) {
		$('#deckcomposition').html("Must have no more than 2 jokers.").addClass('text-danger');
	} else {
		if($('#startingnumbers').size()) {
			var outfit = outfits.first();
			$('#deckcomposition').empty();
			var cost_of_starting_posse = startingposse.select("cost").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
			var upkeep_of_starting_posse = startingposse.select("upkeep").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
			var influence_of_starting_posse = startingposse.select("influence").reduce(function (previousValue, currentValue) { return previousValue+currentValue; }, 0);
			$('#startingnumbers').html('Starting with '+(outfit.wealth-cost_of_starting_posse)+' wealth, '+(outfit.production-upkeep_of_starting_posse)+' income and '+influence_of_starting_posse+' influence');
		}
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
					lines.push(slot.qty + 'x [url=http://dtdb.co/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ']'
					 + slot.card.title
					 + '[/url] [i]('
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
	var types = ["outfit", "event", "hardware", "resource", "icebreaker", "program", "agenda", "asset", "upgrade", "operation", "barrier", "code-gate", "sentry", "none", "multi"];
	var typesstr = ["Outfit", "Event", "Hardware", "Resource", "Icebreaker", "Program", "Agenda", "Asset", "Upgrade", "Operation", "Barrier", "Code Gate", "Sentry", "Other", "Multi"];
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
					lines.push('* '+ slot.qty + 'x ['
					 + slot.card.title 
					 + '](http://dtdb.co/'+DTDB.locale+'/card/'
					 + slot.card.code
					 + ') _('
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
					lines.push(slot.qty + 'x '
					 + slot.card.title
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
