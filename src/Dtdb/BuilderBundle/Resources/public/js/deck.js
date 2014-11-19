var InputByTitle = false;
var DisplayColumns = 1;
var BaseSets = 1;

DTDB.data_loaded.add(function() {
	var localStorageDisplayColumns;
	if (localStorage
			&& (localStorageDisplayColumns = parseInt(localStorage
					.getItem('display_columns'), 10)) !== null
			&& [ 1, 2, 3 ].indexOf(localStorageDisplayColumns) > -1) {
		DisplayColumns = localStorageDisplayColumns;
	}
	$('input[name=display-column-' + DisplayColumns + ']')
			.prop('checked', true);

	var localStorageBaseSets;
	if (localStorage
			&& (localStorageBaseSets = parseInt(localStorage
					.getItem('base_sets'), 10)) !== null
			&& [ 1, 2 ].indexOf(localStorageBaseSets) > -1) {
		BaseSets = localStorageBaseSets;
	}
	$('input[name=base-set-' + BaseSets + ']').prop('checked', true);

	var localStorageSuggestions;
	if (localStorage
			&& (localStorageSuggestions = parseInt(localStorage
					.getItem('show_suggestions'), 10)) !== null
			&& [ 0, 3, 10 ].indexOf(localStorageSuggestions) > -1) {
		DTDB.suggestions.number = localStorageSuggestions;
	}
	$('input[name=show-suggestions-' + DTDB.suggestions.number + ']').prop('checked', true);

	DTDB.data.sets({
		code : "alt"
	}).remove();

	DTDB.data.cards({
		pack_code : "alt"
	}).remove();
	var sets_in_deck = {};
	DTDB.data.cards().each(function(record) {
		var indeck = 0, start = 0;
		if (Deck[record.code]) {
			indeck = parseInt(Deck[record.code].quantity, 10);
			start = Deck[record.code].start;
			sets_in_deck[record.pack_code] = 1;
		}
		DTDB.data.cards(record.___id).update({
			indeck : indeck,
			start: start
		});
	});
	update_deck();
	DTDB.data.cards().each(function(record) {
		var max_qty = 4;
		if (record.pack_code == 'DTR')
			max_qty = Math.min(record.quantity * BaseSets, 4);
		if (record.type_code == "outfit")
			max_qty = 1;
		DTDB.data.cards(record.___id).update({
			maxqty : max_qty
		});
	});

	$('#gang_code').empty();
	$.each(DTDB.data.cards().distinct("gang_code").sort(
			function(a, b) {
				return b === "neutral" ? -1 : a === "neutral" ? 1 : a < b ? -1
						: a > b ? 1 : 0;
			}), function(index, record) {
		$('#gang_code').append(
				'<label class="btn btn-default" data-code="' + record
						+ '"><input type="checkbox" name="' + record
						+ '"><img src="'
						+ Url_GangImage.replace('xxx', record)
						+ '"></label>')
	});
	$('#gang_code').button();
	$('#gang_code').children('label').each(function(index, elt) {
		$(elt).button('toggle');
	});

	$('#suit').empty();
	$.each(DTDB.data.cards().distinct("suit").sort(), function(index, record) {
		$('#suit').append(
				'<label title="' + record
						+ '" class="btn btn-default" data-code="'
						+ record + '"><input type="checkbox" name="' + record
						+ '">&' + (record ? record.toLowerCase() : '#8962') + ';</label>')
	});
	$('#suit').button();
	$('#suit').children('label').each(function(index, elt) {
		if ($(elt).data('code') !== "outfit")
			$(elt).button('toggle');
	});

	$('#pack_code').empty();
	DTDB.data.sets().each(
			function(record) {
				var checked = record.available === ""
						&& sets_in_deck[record.code] == null ? ''
						: ' checked="checked"';
				$('#pack_code').append(
						'<li><a href="#"><label><input type="checkbox" name="'
								+ record.code + '"' + checked + '>'
								+ record.name + '</label></a></li>');
			});

	$('input[name=Outfit]').prop("checked", false);

	$('.filter')
			.each(
					function(index, div) {
						var columnName = $(div).attr('id');
						var arr = [], checked;
						$(div)
								.find("input[type=checkbox]")
								.each(
										function(index, elt) {
											if (columnName == "pack_code"
													&& localStorage
													&& (checked = localStorage
															.getItem('pack_code_'
																	+ $(elt)
																			.attr(
																					'name'))) !== null)
												$(elt).prop('checked',
														checked === "on");
											if ($(elt).prop('checked'))
												arr.push($(elt).attr('name'));
										});
						Filters[columnName] = arr;
					});
	FilterQuery = {};
	$.each(Filters, function(k) {
		if (Filters[k] != '') {
			FilterQuery[k] = Filters[k];
		}
	});

	refresh_collection();

	$('input[name=title]').typeahead({
		name : 'cardnames',
		local : DTDB.data.cards().select('title')
	});
	$('html,body').css('height', 'auto');
	$('.container').show();
});

$(function() {
	$('html,body').css('height', '100%');

	$('input[name=title]').on('typeahead:selected typeahead:autocompleted',
			DTDB.card_modal.typeahead);
	
	$('#pack_code,#search2').on(
			{
				change : handle_input_change,
				click : function(event) {
					var dropdown = $(this).closest('ul').hasClass(
							'dropdown-menu');
					if (dropdown) {
						if (event.shiftKey) {
							if (!event.altKey) {
								$(this).closest(".filter").find(
										"input[type=checkbox]").prop("checked",
										false);
								$(this).children('input[type=checkbox]').prop(
										"checked", true).trigger('change');
							} else {
								$(this).closest(".filter").find(
										"input[type=checkbox]").prop("checked",
										true);
								$(this).children('input[type=checkbox]').prop(
										"checked", false);
							}
						}
						event.stopPropagation();
					} else {
						if (event.shiftKey) {
							if (!event.altKey) {
								$(this).closest(".filter").find("label.active")
										.button('toggle');
							} else {
								$(this).closest(".filter").find(
										"label:not(.active)").button('toggle');
							}
						}
					}
				}
			}, 'label');

	$('#filter-text').on({
		input : function (event) {
			DTDB.smart_filter.handler($(this).val(), refresh_collection);
		}
	});

	$('#save_form').submit(handle_submit);

	$('#btn-save-as-copy').on('click', function(event) {
		$('#deck-save-as-copy').val(1);
	});
	$('#collection').on({
		change : function(event) {
			InputByTitle = false;
			handle_quantity_change.call(this, event);
		}
	}, 'input[type=radio]');
	$('.modal').on({
		change : handle_quantity_change
	}, 'input[type=radio]');
	$('.modal').on({
		change : handle_start_change
	}, 'input[type=checkbox]');
	$('input[name=show-disabled]').on({
		change : function(event) {
			HideDisabled = !$(this).prop('checked');
			refresh_collection();
		}
	});
	$('input[name=only-deck]').on({
		change : function(event) {
			ShowOnlyDeck = $(this).prop('checked');
			refresh_collection();
		}
	});
	$('input[name=display-column-1]').on({
		change : function(event) {
			$('input[name=display-column-2]').prop('checked', false);
			$('input[name=display-column-3]').prop('checked', false);
			DisplayColumns = 1;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=display-column-2]').on({
		change : function(event) {
			$('input[name=display-column-1]').prop('checked', false);
			$('input[name=display-column-3]').prop('checked', false);
			DisplayColumns = 2;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=display-column-3]').on({
		change : function(event) {
			$('input[name=display-column-1]').prop('checked', false);
			$('input[name=display-column-2]').prop('checked', false);
			DisplayColumns = 3;
			if (localStorage)
				localStorage.setItem('display_columns', DisplayColumns);
			refresh_collection();
		}
	});
	$('input[name=base-set-1]').on({
		change : function(event) {
			$('input[name=base-set-2]').prop('checked', false);
			$('input[name=base-set-3]').prop('checked', false);
			BaseSets = 1;
			if (localStorage)
				localStorage.setItem('base_sets', BaseSets);
			update_base_sets();
			refresh_collection();
		}
	});
	$('input[name=base-set-2]').on({
		change : function(event) {
			$('input[name=base-set-1]').prop('checked', false);
			$('input[name=base-set-3]').prop('checked', false);
			BaseSets = 2;
			if (localStorage)
				localStorage.setItem('base_sets', BaseSets);
			update_base_sets();
			refresh_collection();
		}
	});
	$('input[name=show-suggestions-0]').on({
		change : function(event) {
			$('input[name=show-suggestions-3]').prop('checked', false);
			$('input[name=show-suggestions-10]').prop('checked', false);
			DTDB.suggestions.number = 0;
			if (localStorage)
				localStorage.setItem('show_suggestions', DTDB.suggestions.number);
			DTDB.suggestions.show();
		}
	});
	$('input[name=show-suggestions-3]').on({
		change : function(event) {
			$('input[name=show-suggestions-0]').prop('checked', false);
			$('input[name=show-suggestions-10]').prop('checked', false);
			DTDB.suggestions.number = 3;
			if (localStorage)
				localStorage.setItem('show_suggestions', DTDB.suggestions.number);
			DTDB.suggestions.show();
		}
	});
	$('input[name=show-suggestions-10]').on({
		change : function(event) {
			$('input[name=show-suggestions-0]').prop('checked', false);
			$('input[name=show-suggestions-3]').prop('checked', false);
			DTDB.suggestions.number = 10;
			if (localStorage)
				localStorage.setItem('show_suggestions', DTDB.suggestions.number);
			DTDB.suggestions.show();
		}
	});
	$('thead').on({
		click : handle_header_click
	}, 'a[data-sort]');
	$('#cardModal').on({
		keypress : function(event) {
			var num = parseInt(event.which, 10) - 48;
			$('.modal input[type=radio][value=' + num + ']').trigger('change');
		}
	});
	$('#filter-text-button')
			.tooltip(
					{
						html : true,
						container : 'body',
						placement : 'bottom',
						trigger : 'click',
						title : "<h5>Smart filter syntax</h5><ul style=\"text-align:left\"><li>by default, filters on title</li><li>x &ndash; filters on text</li></ul>"
					});
	
	var converter = new Markdown.Converter();
	$('#description').on(
			'keyup',
			function() {
				$('#description-preview').html(
						converter.makeHtml($('#description').val()));
			});

	$('#description').textcomplete(
			[
					{
						match : /\B#([\-+\w]*)$/,
						search : function(term, callback) {
							callback(DTDB.data.cards({
								title : {
									likenocase : term
								}
							}).get());
						},
						template : function(value) {
							return value.title;
						},
						replace : function(value) {
							return '[' + value.title + ']('
									+ Routing.generate('cards_zoom', {card_code:value.code})
									+ ')';
						},
						index : 1
					}
			]);
});
function handle_header_click(event) {
	var new_sort = $(this).data('sort');
	if (Sort == new_sort) {
		Order *= -1;
	} else {
		Sort = new_sort;
		Order = 1;
	}
	$(this).closest('tr').find('th').removeClass('dropup').find('span.caret')
			.remove();
	$(this).after('<span class="caret"></span>').closest('th').addClass(
			Order > 0 ? '' : 'dropup');
	refresh_collection();
}
function handle_input_change(event) {
	var div = $(this).closest('.filter');
	var columnName = div.attr('id');
	var arr = [];
	div.find("input[type=checkbox]").each(
			function(index, elt) {
				if ($(elt).prop('checked'))
					arr.push($(elt).attr('name'));
				if (columnName == "pack_code" && localStorage)
					localStorage.setItem('pack_code_' + $(elt).attr('name'), $(
							elt).prop('checked') ? "on" : "off");
			});
	Filters[columnName] = arr;

	FilterQuery = {};
	$.each(Filters, function(k) {
		if (Filters[k] != '') {
			FilterQuery[k] = Filters[k];
		}
	});
	refresh_collection();
}

function handle_submit(event) {
	var deck_content = {};
	DTDB.data.cards({
		indeck : {
			'gt' : 0
		}
	}).each(function(record) {
		deck_content[record.code] = {
				quantity: record.indeck,
				start: record.start
		};
	});
	var deck_json = JSON.stringify(deck_content);
	$('input[name=content]').val(deck_json);
}

function handle_start_change(event) {
	var index = $(this).closest('.card-container').data('index') || $(this).closest('div.modal').data('index');
	var start = $(this).prop('checked');
	DTDB.data.cards({
		code : index
	}).update({
		start : start ? 1 : 0
	});
	$('div.modal').modal('hide');
	update_deck();
}

function handle_quantity_change(event) {
	var index = $(this).closest('.card-container').data('index')
			|| $(this).closest('div.modal').data('index');
	var in_collection = $(this).closest('#collection').size();
	var quantity = parseInt($(this).val(), 10);
	$(this).closest('.card-container')[quantity ? "addClass" : "removeClass"]('in-deck');
	var card = DTDB.data.cards({ code: index }).first();
	var newdata = { indeck: quantity };
	if(quantity === 0) newdata.start = 0;
	DTDB.data.cards({ code: index }).update(newdata);
	if (card.type_code == "outfit") {
		DTDB.data.cards({
			indeck : {
				'gt' : 0
			},
			type_code : 'outfit',
			code : {
				'!==' : index
			}
		}).update({
			indeck : 0
		});
	}
	update_deck();
	if (card.type_code == "outfit") {
		$.each(CardDivs, function(nbcols, rows) {
			if (rows)
				$.each(rows, function(index, row) {
					row.removeClass("disabled").find('label').removeClass(
							"disabled").find('input[type=radio]').attr(
							"disabled", false);
				});
		});
		refresh_collection();
	} else {
		$.each(CardDivs, function(nbcols, rows) {
			// rows is an array of card rows
			if (rows && rows[index]) {
				// rows[index] is the card row of our card
				rows[index].find('input[name="qty-' + index + '"]').each(
					function(i, element) {
						if ($(element).val() != quantity) {
							$(element).prop('checked', false).closest(
							'label').removeClass('active');
						} else {
							if(!in_collection) {
								$(element).prop('checked', true).closest(
								'label').addClass('active');
							}
						}
					}
				);
			}
		});
	}
	$('div.modal').modal('hide');
	DTDB.suggestions.compute();
	if (InputByTitle)
		$('input[name=title]').typeahead('setQuery', '').focus().blur();
}

function update_base_sets() {
	CardDivs = [ null, {}, {}, {} ];
	DTDB.data.cards({
		pack_code : 'DTR'
	}).each(function(record) {
		var max_qty = Math.min(record.quantity * BaseSets, 4);
		if (record.type_code == "outfit" || record.limited)
			max_qty = 1;
		DTDB.data.cards(record.___id).update({
			maxqty : max_qty
		});
	});
}

function build_div(record) {
	var radios = '';
	for (var i = 0; i <= record.maxqty; i++) {
		radios += '<label class="btn btn-xs btn-default'
				+ (i == record.indeck ? ' active' : '')
				+ '"><input type="radio" name="qty-' + record.code
				+ '" value="' + i + '">' + i + '</label>';
	}

	var div;
	switch (DisplayColumns) {
	case 1:

		var imgsrc = record.gang_code == "neutral" ? "" : '<img src="'
				+ Url_GangImage.replace('xxx', record.gang_code)
				+ '.png">';
		div = $('<tr class="card-container" data-index="'
				+ record.code
				+ '"><td><div class="btn-group" data-toggle="buttons">'
				+ radios
				+ '</div></td><td><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.title + '</a></td><td class="value-' + record.rank
				+ '">' + DTDB.format.rank(record) + '</td><td class="suit" title="' + record.suit
				+ '">' + DTDB.format.suit(record) + '</td><td class="gang" title="' + record.gang + '">'
				+ imgsrc + '</td></tr>');
		break;

	case 2:

		div = $('<div class="col-sm-6 card-container" data-index="'
				+ record.code
				+ '">'
				+ '<div class="media">'
				+ '<a class="pull-left card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ '    <img class="media-object" src="'+record.imagesrc+'">'
				+ '</a>'
				+ '<div class="media-body">'
				+ '    <h4 class="media-heading"><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.title + '</a></h4>'
				+ '    <div class="btn-group" data-toggle="buttons">' + radios
				+ '</div>' + '</div>' + '</div>' + '</div>');
		break;

	case 3:

		div = $('<div class="col-sm-4 card-container" data-index="'
				+ record.code
				+ '">'
				+ '<div class="media">'
				+ '<a class="pull-left card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ '    <img class="media-object" src="'+record.imagesrc+'">'
				+ '</a>'
				+ '<div class="media-body">'
				+ '    <h5 class="media-heading"><a class="card" href="'
				+ Routing.generate('cards_zoom', {card_code:record.code})
				+ '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
				+ record.title + '</a></h5>'
				+ '    <div class="btn-group" data-toggle="buttons">' + radios
				+ '</div>' + '</div>' + '</div>' + '</div>');
		break;

	}

	return div;
}

function is_card_usable(record) {
	return true;
}

function update_filtered() {
	$('#collection-table').empty();
	$('#collection-grid').empty();
	
	var sortType = {
			'rank': ['asec', 'desc'],
			'suit': ['asec', 'desc'],
			'title': ['intl', 'intldesc'],
			'gang': ['intl', 'intldesc'],
			'indeck': ['asec', 'desc']
	};
	var order = sortType[Sort][Order > 0 ? 0 : 1];
	var counter = 0, container = $('#collection-table');
	var SmartFilterQuery = DTDB.smart_filter.get_query(FilterQuery);
	DTDB.data.cards(SmartFilterQuery)
			.order(Sort + ' ' + order + ',title')
			.each(
					function(record) {

						if (ShowOnlyDeck && !record.indeck)
							return;

						var unusable = !is_card_usable(record);

						if (HideDisabled && unusable)
							return;

						var index = record.code;
						var row = (CardDivs[DisplayColumns][index] || (CardDivs[DisplayColumns][index] = build_div(record)))
								.data("index", record.code);
						row.find('input[name="qty-' + record.code + '"]').each(
								function(i, element) {
									if ($(element).val() == record.indeck)
										$(element).prop('checked', true)
												.closest('label').addClass(
														'active');
									else
										$(element).prop('checked', false)
												.closest('label').removeClass(
														'active');
								});

						if (unusable)
							row.find('label').addClass("disabled").find(
									'input[type=radio]').attr("disabled", true);

						if (DisplayColumns > 1
								&& counter % DisplayColumns === 0) {
							container = $('<div class="row"></div>').appendTo(
									$('#collection-grid'));
						}
						container.append(row);
						counter++;
					});
}
var refresh_collection = debounce(update_filtered, 250);
