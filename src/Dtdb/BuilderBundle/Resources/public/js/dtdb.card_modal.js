if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.card_modal = {};
(function(card_modal, $) {
	var modal = null;
	
	card_modal.create_element = function() {
		modal = $('<div class="modal" id="cardModal" tabindex="-1" role="dialog" aria-labelledby="cardModalLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button><h3 class="modal-title">Modal title</h3><div class="row"><div class="col-sm-12 text-center"><div class="btn-group modal-qty" data-toggle="buttons"></div></div></div></div><div class="modal-body"><div class="row"><div class="col-sm-6 modal-image"></div><div class="col-sm-6 modal-info"></div></div></div></div></div></div>');
		modal.appendTo('body');
	};
	
	card_modal.display_modal = function(event) {
		event.preventDefault();
		$(this).qtip('hide');
		var code = $(this).data('index') || $(this).closest('.card-container').data('index');
		fill_modal(code);
	};

	card_modal.typeahead = function (event, data) {
		var card = DTDB.data.cards({title:data.value}).first();
		fill_modal(card.code);
		$('#cardModal').modal('show');
		InputByTitle = true;
	};

	function fill_modal (code) {
		var card = DTDB.data.cards({code:code}).first();
		modal.data('index', code);
		modal.find('h3.modal-title').html((card.uniqueness ? "&diams; " : "")+card.title);
		modal.find('.modal-image').html('<img class="img-responsive" src="'+card.imagesrc+'">');
		modal.find('.modal-info').html(
		  '<div class="card-info">'+DTDB.format.type(card)+'</div>'
		  +'<div><small>' + card.gang + ' &bull; '+ card.pack + '</small></div>'
		  +'<div class="card-text"><small>'+DTDB.format.text(card)+'</small></div>'
		);

		var qtyelt = modal.find('.modal-qty');
		if(qtyelt && typeof Filters != "undefined") {

			var qty = '';
		  	for(var i=0; i<=card.maxqty; i++) {
		  		qty += '<label class="btn btn-default"><input type="radio" name="qty" value="'+i+'">'+i+'</label>';
		  	}
		  	qtyelt.html(qty);
		   	
		  	qtyelt.find('label').each(function (index, element) {
				if(index == card.indeck) $(element).addClass('active');
				else $(element).removeClass('active');
			});
			if(card.code == Outfit.code) {
				qtyelt.find('label').addClass("disabled").find('input[type=radio]').attr("disabled", true);
			}
			
			$('#starting').remove();
			if(card.type_code == 'dude') {
				var canstart = card.gang_code === 'neutral' || card.gang_code === Outfit.gang_code;				
				if(canstart) {
					if (card.keywords.search('Non-Unique') != -1){
					var start = '<div class="btn-group modal-start" data-toggle="buttons" id="starting" style="margin-left:5px"></div>';
					qtyelt.after(start);
					
					var startelt = modal.find('.modal-start');
					if(startelt) {
						start = '<label class="btn btn-default disabled">Starting</label>';
					  	for(var i=0; i<=card.maxqty; i++) {
					  		start += '<label class="btn btn-default"><input type="radio" name="start" value="'+i+'">'+i+'</label>';
					  	}
					  	startelt.html(start);
					   	
					  	startelt.find('label').each(function (index, element) {
							if(index == card.start + 1) $(element).addClass('active');
							else $(element).removeClass('active');
						});					  	
					}
					} else {
						var start = $('<div class="btn-group" data-toggle="buttons" id="starting" style="margin-left:5px"><label class="btn btn-default'+(card.start ? ' active' : '')+'"><input type="checkbox"'+(card.start ? ' checked' : '')+'>Starting</label></div>');
						qtyelt.after(start);
					}
				}
			} else if (card.type_code == 'deed'){
				var canstart = card.keywords.search('Core') != -1;
				if(canstart) {
					var start = $('<div class="btn-group" data-toggle="buttons" id="starting" style="margin-left:5px"><label class="btn btn-default'+(card.start ? ' active' : '')+'"><input type="checkbox"'+(card.start ? ' checked' : '')+'>Starting</label></div>');
					qtyelt.after(start);
				}
				
			}
		} else {
			if(qtyelt) qtyelt.closest('.row').remove();
		}
	}


	$(function () {
		card_modal.create_element();
		$('body').on({click: card_modal.display_modal}, '.card');
	});
	
})(DTDB.card_modal, jQuery);
