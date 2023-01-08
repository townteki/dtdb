
DTDB.data_loaded.add(function() {
	DTDB.data.sets({
		code : "alt"
	}).remove();

	DTDB.data.cards({
		pack_code : "alt"
	}).remove();
	var sets_in_deck = {};
	DTDB.data.cards().each(function(record) {
		var indeck = 0, start = 0;
		if (SelectedDeck.slots[record.code]) {
			indeck = parseInt(SelectedDeck.slots[record.code].quantity, 10);
			start = SelectedDeck.slots[record.code].start;
			sets_in_deck[record.pack_code] = 1;
		}
		DTDB.data.cards(record.___id).update({
			indeck : indeck,
			start: start
		});
	});
	update_deck();
	DTDB.deck_gallery.update();

	$('html,body').css('height', 'auto');
	$('.container').show();
});


function do_action_deck(event) {
	var action_id = $(this).attr('id');
	if(!action_id || !SelectedDeck) return;
	switch(action_id) {
		case 'btn-edit': location.href=Routing.generate('deck_edit', {deck_id:SelectedDeck.id}); break;
		case 'btn-publish': confirm_publish(); break;
		case 'btn-delete': confirm_delete(); break;
		case 'btn-download-text': location.href=Routing.generate('deck_export_text', {deck_id:SelectedDeck.id}); break;
		case 'btn-download-octgn': location.href=Routing.generate('deck_export_octgn', {deck_id:SelectedDeck.id}); break;
		case 'btn-print': window.print(); break;
		case 'btn-sort-suit': DisplaySort = 'suit'; switch_to_web_view(); break;
		case 'btn-sort-type': DisplaySort = 'type'; switch_to_web_view(); break;
		case 'btn-sort-number': DisplaySort = 'number'; switch_to_web_view(); break;
		case 'btn-sort-gang': DisplaySort = 'gang'; switch_to_web_view(); break;
		case 'btn-sort-rank': DisplaySort = 'rank'; switch_to_web_view(); break;
		case 'btn-sort-title': DisplaySort = 'title'; switch_to_web_view(); break;
		case 'btn-display-plain': $('#deck').html(build_plaintext().join('<br>')); break;
		case 'btn-display-bbcode': $('#deck').html(build_bbcode().join('<br>')); break;
		case 'btn-display-markdown': $('#deck').html(build_markdown().join('<br>')); break;
		case 'btn-display-links': display_links(); breaks;
		case 'btn-display-no-links': display_no_links(); breaks;
	}
}

function display_links() {
	$('span.card').each(function (index, element) {
		$(element).replaceWith('<a class="card" href="'+$(element).data('href')+'" data-toggle="modal" data-index="'+$(element).data('index')+'">'+$(element).html()+'</span>');
	});
}

function display_no_links() {
	$('a.card').each(function (index, element) {
		$(element).replaceWith('<span class="card" data-href="'+$(element).attr('href')+'" data-index="'+$(element).data('index')+'">'+$(element).html()+'</span>');
	});
}

$(function() {
	$('html,body').css('height', '100%');

	$('#cardModal').on({
		keypress : function(event) {
			var num = parseInt(event.which, 10) - 48;
			$('.modal input[type=radio][value=' + num + ']').trigger('change');
		}
	});

	var converter = new Markdown.Converter();
	$('#description').html(converter.makeHtml(SelectedDeck.description ? SelectedDeck.description : '<i>No description.</i>'));

	$('#btn-group-deck').on({
		click: do_action_deck
	}, 'button[id],a[id]');

	$('#btn-publish').prop('disabled', !!SelectedDeck.problem);

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


function switch_to_web_view() {
	$('#deck').html('<div class="row"><div class="col-sm-12"><h3 id="outfit"></h3><h5 id="legend"></h5><div id="startingnumbers"></div><div id="cardcount"></div><div id="latestpack"></div></div></div><div class="row" id="deck-content" style="margin-bottom:10px"></div>');
	update_deck();
}
