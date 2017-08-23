DTDB.data_loaded.add(function() {
	DTDB.data.sets({
		code : "alt"
	}).remove();

	DTDB.data.cards({
		pack_code : "alt"
	}).remove();

	$(this).closest('tr').siblings().removeClass('active');
	$(this).closest('tr').addClass('active');
	for (var i = 0; i < Decklist.cards.length; i++) {
		var slot = Decklist.cards[i];
		DTDB.data.cards({
			code : slot.card_code
		}).update({
			indeck : parseInt(slot.qty, 10),
			start: parseInt(slot.start, 10)
		});
	}
	update_deck();
});

function setup_comment_form() {

	var form = $('<form method="POST" action="'+Routing.generate('decklist_comment')+'"><input type="hidden" name="id" value="'+Decklist.id+'"><div class="form-group">'
			+ '<textarea id="comment-form-text" class="form-control" rows="4" name="comment" placeholder="Enter your comment in Markdown format. Type # to enter a card name. Type @ to enter a user name."></textarea>'
			+ '</div><div class="well text-muted" id="comment-form-preview"><small>Preview. Look <a href="http://daringfireball.net/projects/markdown/dingus">here</a> for a Markdown syntax reference.</small></div>'
			+ '<button type="submit" class="btn btn-success">Submit comment</button></form>').insertAfter('#comment-form');
	form.on('submit', function (event) {
		var data = $(this).serialize();
		$.ajax(Routing.generate('decklist_comment'), {
			data: data,
			type: 'POST',
			success: function(data, textStatus, jqXHR) {
				form.replaceWith('<div class="alert alert-success" role="alert">Your comment has been posted. It will appear on the site in a few minutes.</div>');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				form.replaceWith('<div class="alert alert-danger" role="alert">An error occured while posting your comment ('+jqXHR.statusText+'). Reload the page and try again.</div>');
			}
		});
		event.preventDefault();
	});

	$('#decklist-social-icon-comment').on('click', function() {
		$('#comment-form-text').trigger('focus');
	});


	var converter = new Markdown.Converter();
	$('#comment-form-text').on(
		'keyup',
		function() {
			$('#comment-form-preview').html(converter.makeHtml($('#comment-form-text').val()));
		}
	);

	$('#comment-form-text').textcomplete(
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
					}, {
						match : /\B@([\-+\w]*)$/,
						search : function(term, callback) {
							var regexp = new RegExp('^' + term);
							callback($.grep(Commenters, function(commenter) {
								return regexp.test(commenter);
							}));
						},
						template : function(value) {
							return value;
						},
						replace : function(value) {
							return '`@' + value + '`';
						},
						index : 1
					} ]);

}

function setup_social_icons() {

	if(!DTDB.user.data || DTDB.user.data.is_author || DTDB.user.data.is_liked) {
		var element = $('#decklist-social-icon-like');
		element.replaceWith($('<span class="social-icon-like"></span').html(element.html()));
	}

	if(!DTDB.user.data) {
		var element = $('#decklist-social-icon-favorite');
		element.replaceWith($('<span class="social-icon-favorite"></span').html(element.html()));
	} else if(DTDB.user.data.is_favorite) {
		var element = $('#decklist-social-icon-favorite');
		element.attr('title', "Remove from favorites");
	} else {
		var element = $('#decklist-social-icon-favorite');
		element.attr('title', "Add to favorites");
	}

	if(!DTDB.user.data) {
		var element = $('#decklist-social-icon-comment');
		element.replaceWith($('<span class="social-icon-comment"></span').html(element.html()));
	}

	$('#decklist-social-icons>a').tooltip();

}

function setup_title() {
	var title = $('#decklist_title');
	if(DTDB.user.data && DTDB.user.data.is_author && DTDB.user.data.can_delete) {
		title.prepend('<a href="#" title="Delete decklist" id="decklist-delete"><span class="glyphicon glyphicon-trash pull-right text-danger"></span></a>');
	}
	if(DTDB.user.data && DTDB.user.data.is_author) {
		title.prepend('<a href="#" title="Edit decklist name / description" id="decklist-edit"><span class="glyphicon glyphicon-pencil pull-right"></span></a>');
	}
}

$(function() {

	$.when(DTDB.user.deferred).then(function() {
		if(DTDB.user.data) {
			setup_comment_form();
			setup_title();
		} else {
			$('<p>You must be logged in to post comments.</p>').insertAfter('#comment-form');
		}
		setup_social_icons();
	})

	$(document).on('click', '#decklist-edit', edit_form);
	$(document).on('click', '#decklist-delete', delete_form);
	$(document).on('click', '#decklist-social-icon-like', send_like);
	$(document).on('click', '#decklist-social-icon-favorite', send_favorite);
	$(document).on('click', '#btn-group-decklist button[id],a[id]', do_action_decklist);
	$(document).on('click', '#btn-compare', compare_form);
	$(document).on('click', '#btn-compare-submit', compare_submit);

	$('#menu-sort').on({
		change : function(event) {
			if ($(this).attr('id').match(/btn-sort-(\w+)/)) {
				DisplaySort = RegExp.$1;
				update_deck();
			}
		}
	}, 'a');

});

function compare_submit() {
	var url = $('#decklist2_url').val();
	var id;
	if(url.match(/^\d+$/)) {
		id = parseInt(url, 10);
	} else if(url.match(/decklist\/(\d+)\//)) {
		id = parseInt(RegExp.$1, 10);
	}
	if(id) {
		var id1, id2;
		if(Decklist.id < id) {
			id1 = Decklist.id;
			id2 = id;
		} else {
			id1 = id;
			id2 = Decklist.id;
		}
		location.href = Routing.generate('decklists_diff', {decklist1_id:id1, decklist2_id:id2});
	}
}

function compare_form() {
	$('#compareModal').modal('show');
	setTimeout(function () {
		$('#decklist2_url').focus();
	}, 1000);
}

function edit_form() {
	$('#editModal').modal('show');
}

function delete_form() {
	$('#deleteModal').modal('show');
}

function do_action_decklist(event) {
	var action_id = $(this).attr('id');
	if (!action_id || !SelectedDeck)
		return;
	switch (action_id) {
	case 'btn-download-text':
		location.href = Routing.generate('decklist_export_text', {decklist_id:Decklist.id});
		break;
	case 'btn-download-octgn':
		location.href = Routing.generate('decklist_export_octgn', {decklist_id:Decklist.id});
		break;
	case 'btn-export-bbcode':
		export_bbcode();
		break;
	case 'btn-export-markdown':
		export_markdown();
		break;
	case 'btn-export-plaintext':
		export_plaintext();
		break;
	}
}

function send_like() {
	var obj = $(this);
	$.post(Routing.generate('decklist_like'), {
		id : Decklist.id
	}, function(data, textStatus, jqXHR) {
		obj.find('.num').text(data);
	});
}

function send_favorite() {
	var obj = $(this);
	$.post(Routing.generate('decklist_favorite'), {
		id : Decklist.id
	}, function(data, textStatus, jqXHR) {
		obj.find('.num').text(data);
		var title = obj.data('original-tooltip');
		obj.tooltip('destroy');
		obj.data('original-tooltip',
				title == "Add to favorites" ? "Remove from favorites"
						: "Add to favorites");
		obj.attr('title', obj.data('original-tooltip'));
		obj.tooltip('show');
	});
}

$(window).load(function() {
	var imageSrc = $('#img_legend').attr('src');
  if ( imageSrc === "" ) {
      	$("#decksummary").removeClass('col-sm-6').addClass('col-sm-9');
			}
});
