if (typeof DTDB != "object")
	var DTDB = { data_loaded: jQuery.Callbacks() };
DTDB.format = {};
(function(format, $) {
	
	format.type = function(card) {
		var type = '<span class="card-type">'+card.type+'</span>';
		if(card.keywords) type += '<span class="card-keywords">: '+card.keywords+'</span>';
		if(card.bullets !== null) type += ' &bull; <span class="card-prop">Bullets '+card.bullets+'</span>';
		if(card.influence !== null) type += ' &bull; <span class="card-prop">Influence '+card.influence+'</span>';
		if(card.control !== null) type += ' &bull; <span class="card-prop">Control '+card.control+'</span>';
		if(card.cost !== null) type += ' &bull; <span class="card-prop">Cost '+card.cost+'</span>';
		if(card.wealth !== null) type += ' &bull; <span class="card-prop">Wealth '+card.wealth+'</span>';
		if(card.production !== null) type += ' &bull; <span class="card-prop">Production '+card.production+'</span>';
		if(card.upkeep !== null) type += ' &bull; <span class="card-prop">Upkeep '+card.upkeep+'</span>';
		return type;
	};

	format.text = function(card) {
		var text = card.text;
		
		text = text.replace(/\[Subroutine\]/g, '<span class="icon icon-subroutine"></span>');
		text = text.replace(/\[Credits\]/g, '<span class="icon icon-credit"></span>');
		text = text.replace(/\[Trash\]/g, '<span class="icon icon-trash"></span>');
		text = text.replace(/\[Click\]/g, '<span class="icon icon-click"></span>');
		text = text.replace(/\[Recurring Credits\]/g, '<span class="icon icon-recurring-credit"></span>');
		text = text.replace(/\[Memory Unit\]/g, '<span class="icon icon-mu"></span>');
		text = text.replace(/\[Link\]/g, '<span class="icon icon-link"></span>');
		text = text.split("\n").join('</p><p>');
		return '<p>'+text+'</p>';
	};
	
	format.face = function(card) {
		if(!card.suit) return '';
		
		var drank = card.rank;
		if(card.rank == 1) drank = 'A';
		if(card.rank == 11) drank = 'J';
		if(card.rank == 12) drank = 'Q';
		if(card.rank == 13) drank = 'K';
		
		var dsuit = card.suit.toLowerCase(); 
		return '<span class="card-face card-face-'+dsuit+'"><span class="card-rank rank-'+card.rank+'">'+drank+'</span><span class="card-suit suit-'+dsuit+'"></span></span>';
	};

})(DTDB.format, jQuery);
