if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };

DTDB.draw_simulator = {};
(function(draw_simulator, $) {
	var deck = null, initial_size = 0, draw_count = 0, container = null;
	
	draw_simulator.reset = function() {
		if(container) container.empty();
		deck = null;
		initial_size = draw_count = 0;
		update_odds();
		$('#draw-simulator-clear').attr('disabled', true);
	};
	
	draw_simulator.init = function() {
		container = $('#table-draw-simulator-content');
		deck = [];
		DTDB.data.cards({indeck:{'gt':0},type_code:{'!is':'outfit'}}).each(function (record) {
			for(var ex = 0; ex < record.indeck; ex++) {
				deck.push(record);
			}
		});
		initial_size = deck.length;
	}

	function update_odds() {
		for(var i=1; i<=3; i++) {
			var odd = hypergeometric.get_cumul(1, initial_size, i, draw_count);
			$('#draw-simulator-odds-'+i).text(Math.round(100*odd));
		}
	}
	
	function do_draw(draw) {
		for(var pick = 0; pick < draw && deck.length > 0; pick++) {
			var rand = Math.floor(Math.random() * deck.length);
			var spliced = deck.splice(rand, 1);
			var card = spliced[0];
			container.append('<img src="'+card.imagesrc+'" class="card" data-index="'+card.code+'">');
			$('#draw-simulator-clear').attr('disabled', false);
			draw_count++;
		}
		update_odds();
	}
	
	draw_simulator.handle_click = function(event) {

		event.preventDefault();
		var id = $(this).attr('id');
		var command = id.substr(15);
		
		if(command === 'clear') {
			draw_simulator.reset();
			return;
		}
		if(event.shiftKey) {
			draw_simulator.reset();
		}
		if(deck === null) {
			draw_simulator.init();
		}
		var draw;
		if(command === 'all') {
			draw = deck.length;
		} else {
			draw = parseInt(command, 10);
		}
		if(isNaN(draw)) return;
		do_draw(draw);

	};
	
	draw_simulator.toggle_opacity = function(event) {
		$(this).css('opacity', 1.5 - parseFloat($(this).css('opacity')));
	};

	$(function () {
		$('#table-draw-simulator').on({click: draw_simulator.handle_click}, 'a.btn');
		$('#table-draw-simulator').on({click: draw_simulator.toggle_opacity}, 'img.card');
	});
	
})(DTDB.draw_simulator, jQuery);
