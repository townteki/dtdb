DTDB.data_loaded.add(function() {
	DTDB.data.sets({code:"alt"}).remove();
	DTDB.data.cards({pack_code:"alt"}).remove();
});

$(function() {
	$('#btn-group-deck').on('click', 'button[id],a[id]', do_action_deck);
	$('#btn-group-selection').on('click', 'button[id],a[id]', do_action_selection);
	$('#btn-group-sort').on('click', 'button[id],a[id]', do_action_sort);
	$('#decks_upload_all').on('click', decks_upload_all);
	
	$('#menu-sort').on({
		change: function(event) {
			if($(this).attr('id').match(/btn-sort-(\w+)/)) {
				DisplaySort = RegExp.$1;
				update_deck();
			}
		}
	}, 'a');
	
	$('#tag_toggles').on('click', 'button', function (event) {
		var button = $(this);
		if(!event.shiftKey) {
			$('#tag_toggles button').each(function (index, elt) {
				if($(elt).text() != button.text()) $(elt).removeClass('active');
			});
		}
		setTimeout(filter_decks, 0);
	});
	update_tag_toggles();
	
	$('#decks').on('click', 'a.deck-list-group-item', function (event) {
		if(!event.shiftKey) {
			$('#decks a.deck-list-group-item.selected').removeClass('selected');
			$(this).addClass('selected');
		} else {
			$(this).toggleClass('selected');
		}
		if($(this).hasClass('selected')) {
			var deck_id = $(this).data('id').toString();
			select_deck(deck_id);
			if(!event.shiftKey) {
				show_deck();
			}
		} else {
			hide_deck();
		}
		return false;
	});
	$('#decks').on('click', '#close_deck', function (event) {
		hide_deck();
		return false;
	});
	$(document).keyup(function (event) {
		if(event.which == 27) {
			hide_deck();
	    }
		if(event.which == 13) {
			show_deck();
	    }
	});

	$('#publish-deck-description').textcomplete(
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

function decks_upload_all() {
	$('#archiveModal').modal('show');
}

function do_diff(ids) {
	if(ids.length < 2) return;
	
	var contents = [];
	var names = [];
	for(var decknum=0; decknum<ids.length; decknum++) {
		var deck = DeckDB({id:String(ids[decknum])}).first();
		var hash = {};
		for(var slotnum=0; slotnum<deck.cards.length; slotnum++) {
			var slot = deck.cards[slotnum];
			hash[slot.card_code] = slot.qty;
		}
		contents.push(hash);
		names.push(deck.name);
	}
	
	var diff = DTDB.diff.compute_simple(contents);
	var listings = diff[0];
	var intersect = diff[1];
	
	var container = $('#diff_content');
	container.empty();
	container.append("<h4>Cards in all decks</h4>");
	var list = $('<ul></ul>').appendTo(container);
	$.each(intersect, function (card_code, qty) {
		var card = DTDB.data.cards({code:card_code}).first();
		if(card) list.append('<li>'+card.title+' x'+qty+'</li>');
	});
	
	for(var i=0; i<listings.length; i++) {
		container.append("<h4>Cards only in <b>"+names[i]+"</b></h4>");
		var list = $('<ul></ul>').appendTo(container);
		$.each(listings[i], function (card_code, qty) {
			var card = DTDB.data.cards({code:card_code}).first();
			if(card) list.append('<li>'+card.title+' x'+qty+'</li>');
		});
	}
	$('#diffModal').modal('show');
}

function do_diff_collection(ids) {
	if(ids.length < 2) return;
	var decks = [];
	
	var ensembles = [];
	var lengths = [];
	for(var decknum=0; decknum<ids.length; decknum++) {
		var deck = DeckDB({id:String(ids[decknum])}).first();
		decks.push(deck);
		var cards = [];
		for(var slotnum=0; slotnum<deck.cards.length; slotnum++) {
			var slot = deck.cards[slotnum];
			for(var copynum=0; copynum<slot.qty; copynum++) {
				cards.push(slot.card_code);
			}
		}
		ensembles.push(cards);
		lengths.push(cards.length);
	}
	
	var imax = 0;
	for(var i=0; i<lengths.length; i++) {
		if(lengths[imax] < lengths[i]) imax = i;
	}
	var collection = ensembles.splice(imax, 1);
	var rest = [];
	for(var i=0; i<ensembles.length; i++) {
		rest = rest.concat(ensembles[i]);
	}
	ensembles = [collection[0], rest];
	var names = [decks[imax].name, "The rest"];
	
	var conjonction = [];
	for(var i=0; i<ensembles[0].length; i++) {
		var code = ensembles[0][i];
		var indexes = [ i ];
		for(var j=1; j<ensembles.length; j++) {
			var index = ensembles[j].indexOf(code);
			if(index > -1) indexes.push(index);
			else break;
		}
		if(indexes.length === ensembles.length) {
			conjonction.push(code);
			for(var j=0; j<indexes.length; j++) {
				ensembles[j].splice(indexes[j], 1);
			}
			i--;
		}
	}
	
	var listings = [];
	for(var i=0; i<ensembles.length; i++) {
		listings[i] = array_count(ensembles[i]);
	}
	var intersect = array_count(conjonction);
	
	var container = $('#diff_content');
	container.empty();
	container.append("<h4>Cards in all decks</h4>");
	var list = $('<ul></ul>').appendTo(container);
	$.each(intersect, function (card_code, qty) {
		var card = DTDB.data.cards({code:card_code}).first();
		if(card) list.append('<li>'+card.title+' x'+qty+'</li>');
	});

	for(var i=0; i<listings.length; i++) {
		container.append("<h4>Cards only in <b>"+names[i]+"</b></h4>");
		var list = $('<ul></ul>').appendTo(container);
		$.each(listings[i], function (card_code, qty) {
			var card = DTDB.data.cards({code:card_code}).first();
			if(card) list.append('<li>'+card.title+' x'+qty+'</li>');
		});
	}
	$('#diffModal').modal('show');
}

// takes an array of strings and returns an object where each string of the array
// is a key of the object and the value is the number of occurences of the string in the array
function array_count(list) {
	var obj = {};
	var list = list.sort();
	for(var i=0; i<list.length; ) {
		for(var j=i+1; j<list.length; j++) {
			if(list[i] !== list[j]) break;
		}
		obj[list[i]] = (j-i);
		i=j;
	}
	return obj;
}

function filter_decks() {
	var buttons = $('#tag_toggles button.active');
	var list_id = [];
	buttons.each(function (index, button) {
		list_id = list_id.concat($(button).data('deck_id').split(/\s+/));
	});
	list_id = list_id.filter(function (itm,i,a) { return i==a.indexOf(itm); });
	$('#decks a.deck-list-group-item').each(function (index, elt) {
		$(elt).removeClass('selected');
		var id = $(elt).attr('id').replace('deck_', '');
		if(list_id.length && list_id.indexOf(id) === -1) $(elt).hide();
		else $(elt).show();
	});
}

function do_action_deck(event) {
	var action_id = $(this).attr('id');
	if(!action_id || !SelectedDeck) return;
	switch(action_id) {
		case 'btn-view': location.href=Routing.generate('deck_view', {deck_id:SelectedDeck.id}); break;
		case 'btn-edit': location.href=Routing.generate('deck_edit', {deck_id:SelectedDeck.id}); break;
		case 'btn-publish': confirm_publish(); break;
		case 'btn-duplicate': location.href=Routing.generate('deck_duplicate', {deck_id:SelectedDeck.id}); break;
		case 'btn-delete': confirm_delete(); break;
		case 'btn-download-text': location.href=Routing.generate('deck_export_text', {deck_id:SelectedDeck.id}); break;
		case 'btn-download-octgn': location.href=Routing.generate('deck_export_octgn', {deck_id:SelectedDeck.id}); break;
		case 'btn-export-bbcode': export_bbcode(); break;
		case 'btn-export-markdown': export_markdown(); break;
		case 'btn-export-plaintext': export_plaintext(); break;
	}
}

function do_action_selection(event) {
	var action_id = $(this).attr('id');
	var ids = [];
	$('#decks a.deck-list-group-item.selected').each(function (index, elt) { ids.push($(elt).data('id')) });
	if(!action_id || !ids.length) return;
	switch(action_id) {
		case 'btn-compare': do_diff(ids); break;
		case 'btn-compare-collection': do_diff_collection(ids); break;
		case 'btn-tag-add': tag_add(ids); break;
		case 'btn-tag-remove-one': tag_remove(ids); break;
		case 'btn-tag-remove-all': tag_clear(ids); break;
		case 'btn-delete-selected': confirm_delete_all(ids); break;
		case 'btn-download-text': download_text_selection(ids); break;
		case 'btn-download-octgn': download_octgn_selection(ids); break;
	}
}

function do_action_sort(event) {
	var action_id = $(this).attr('id');
	if(!action_id) return;
	switch(action_id) {
		case 'btn-sort-update': sort_list('dateupdate'); break;
		case 'btn-sort-creation': sort_list('datecreation'); break;
		case 'btn-sort-outfit': sort_list('outfit_title,name'); break;
		case 'btn-sort-gang': sort_list('gang_code,name'); break;
		case 'btn-sort-lastpack': sort_list('cycle_id,pack_number'); break;
		case 'btn-sort-name': sort_list('name'); break;
	}
}

function download_text_selection(ids) 
{
	window.location = Routing.generate('deck_export_text_list', { ids: ids });
}

function download_octgn_selection(ids)
{
	window.location = Routing.generate('deck_export_octgn_list', { ids: ids });
}

function sort_list(type)
{
	var container = $('#decks');
    var current_sort = container.data('sort-type');
    var current_order = container.data('sort-order');
    var order = current_order || 1;
    if(current_sort && current_sort == type) {
        order = -order;
    }
    container.data('sort-type', type);
    container.data('sort-order', order);
    var sort = type.split(/,/).map(function (t) { return t+' '+(order > 0 ? 'desc' : 'asec') }).join(',');
	var sorted_list_id = DeckDB().order(sort).select('id');
	var first_id = sorted_list_id.shift();
	var deck_elt = $('#deck_'+first_id);
	
	container.prepend(deck_elt);
	sorted_list_id.forEach(function (id) {
		deck_elt = $('#deck_'+id).insertAfter(deck_elt);
	})
	
}


function update_tag_toggles()
{

	// tags is an object where key is tag and value is array of deck ids
	var tag_dict = Decks.reduce(function (p, c) {
		c.tags.forEach(function (t) {
			if(!p[t]) p[t] = [];
			p[t].push(c.id);
		});
		return p;
	}, {});
	var tags = [];
	for(var tag in tag_dict) {
		tags.push(tag);
	}
	var container = $('#tag_toggles').empty();
	tags.sort().forEach(function (tag) {
		$('<button type="button" class="btn btn-default btn-xs" data-toggle="button">'+tag+'</button>').data('deck_id', tag_dict[tag].join(' ')).appendTo(container);
	});
	
}

function set_tags(id, tags)
{
	var elt = $('#deck_'+id);
	var div = elt.find('.deck-list-tags').empty();
	tags.forEach(function (tag) {
		div.append($('<span class="label label-default tag-'+tag+'">'+tag+'</span>'));
	})
	
	for(var i=0; i<Decks.length; i++) {
		if(Decks[i].id == id) {
			Decks[i].tags = tags;
			break;
		}
	}
	
	update_tag_toggles();
}

function tag_add(ids) {
    $('#tag_add_ids').val(ids);
	$('#tagAddModal').modal('show');
    setTimeout(function() { $('#tag_add_tags').focus() }, 500);
}
function tag_add_process(event) {
    event.preventDefault();
    var ids = $('#tag_add_ids').val().split(/,/);
    var tags = $('#tag_add_tags').val().split(/\s+/);
    if(!ids.length || !tags.length) return;
	$.ajax(Routing.generate('tag_add'), {
		type: 'POST',
		data: { ids: ids, tags: tags },
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			var response = jqXHR.responseJSON;
			if(!response.success) {
				alert('An error occured while updating the tags.');
				return;
			}
			$.each(response.tags, function (id, tags) {
				set_tags(id, tags);
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert('An error occured while updating the tags.');
		}
	});
}

function tag_remove(ids) {
    $('#tag_remove_ids').val(ids);
	$('#tagRemoveModal').modal('show');
    setTimeout(function() { $('#tag_remove_tags').focus() }, 500);
}
function tag_remove_process(event) {
    event.preventDefault();
    var ids = $('#tag_remove_ids').val().split(/,/);
    var tags = $('#tag_remove_tags').val().split(/\s+/);
    if(!ids.length || !tags.length) return;
	$.ajax(Routing.generate('tag_remove'), {
		type: 'POST',
		data: { ids: ids, tags: tags },
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			var response = jqXHR.responseJSON;
			if(!response.success) {
				alert('An error occured while updating the tags.');
				return;
			}
			$.each(response.tags, function (id, tags) {
				set_tags(id, tags);
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert('An error occured while updating the tags.');
		}
	});
}

function tag_clear(ids) {
    $('#tag_clear_ids').val(ids);
	$('#tagClearModal').modal('show');
}
function tag_clear_process(event) {
    event.preventDefault();
    var ids = $('#tag_clear_ids').val().split(/,/);
    if(!ids.length) return;
	$.ajax(Routing.generate('tag_clear'), {
		type: 'POST',
		data: { ids: ids },
		dataType: 'json',
		success: function(data, textStatus, jqXHR) {
			var response = jqXHR.responseJSON;
			if(!response.success) {
				alert('An error occured while updating the tags.');
				return;
			}
			$.each(response.tags, function (id, tags) {
				set_tags(id, tags);
			});
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert('An error occured while updating the tags.');
		}
	});
}

function confirm_publish() {
	$('#publish-form-alert').remove();
	$('#btn-publish-submit').text("Checking...").prop('disabled', true);
	$.ajax(Routing.generate('deck_publish', {deck_id:SelectedDeck.id}), {
	  success: function( response ) {
		  if(response == "") {
			  $('#btn-publish-submit').text("Go").prop('disabled', false);
		  }
		  else 
		  {
			  $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">That deck cannot be published because <a href="'+response+'">another decklist</a> already has the same composition.</div>');
			  $('#btn-publish-submit').text("Refused");
		  }
	  },
	  error: function( jqXHR, textStatus, errorThrown ) {
	    $('#publish-deck-form').prepend('<div id="publish-form-alert" class="alert alert-danger">'+jqXHR.responseText+'</div>');
	  }
	});
	$('#publish-deck-name').val(SelectedDeck.name);
	$('#publish-deck-id').val(SelectedDeck.id);
	$('#publish-deck-description').val(SelectedDeck.description);
	$('#publishModal').modal('show');
}

function confirm_delete() {
	$('#delete-deck-name').text(SelectedDeck.name);
	$('#delete-deck-id').val(SelectedDeck.id);
	$('#deleteModal').modal('show');
}

function confirm_delete_all(ids) {
	$('#delete-deck-list-id').val(ids.join('-'));
	$('#deleteListModal').modal('show');
}

function hide_deck() {
	$('#deck').hide();
	$('#close_deck').remove();
}

function unselect_deck() {
	SelectedDeck.id = null;
}

function select_deck(deck_id) {
	DTDB.data.cards().update({indeck:0});
	var deck = DeckDB({id:deck_id}).first();
	SelectedDeck = deck;
}

function show_deck() {
	var container = $('#deck_'+SelectedDeck.id);
	$('#no-deck-selected').hide();
	$('#deck').appendTo(container);
	$('#deck').show();

	$('#close_deck').remove();
	$('<button type="button" class="close" id="close_deck"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>').prependTo(container);
	
	$(this).closest('tr').siblings().removeClass('active');
	$(this).closest('tr').addClass('active');
	for(var i=0; i<SelectedDeck.cards.length; i++) {
		var slot = SelectedDeck.cards[i];
		DTDB.data.cards({code:slot.card_code}).update({indeck:slot.qty,start:slot.start});
	}
	$('#deck-name').text(SelectedDeck.name);
	
	update_deck();
	// convert date from UTC to local
	$('#datecreation').html('<small>Creation: '+moment(SelectedDeck.datecreation).format('LLLL')+'</small>');
	$('#dateupdate').html('<small>Last update: '+moment(SelectedDeck.dateupdate).format('LLLL')+'</small>');
	$('#btn-publish').prop('disabled', SelectedDeck.problem || SelectedDeck.unsaved);
}
