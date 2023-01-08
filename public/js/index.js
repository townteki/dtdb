DTDB.data_loaded.add(function () {
    DTDB.data.sets({
        code: "alt"
    }).remove();

    DTDB.data.cards({
        pack_code: "alt"
    }).remove();

    for (var i = 0; i < Decklist.cards.length; i++) {
        var slot = Decklist.cards[i];
        DTDB.data.cards({
            code: slot.card_code
        }).update({
            indeck: parseInt(slot.qty, 10),
            start: parseInt(slot.start, 10)
        });
    }
    update_deck();
    DTDB.deck_browser.update();
});

function update_cardsearch_result() {
    $('#card_search_results').empty();
    var query = DTDB.smart_filter.get_query();
    if ($.isEmptyObject(query))
        return;
    var tabindex = 2;
    DTDB.data.cards(query).order("title asec").each(
        function (record) {
            $('#card_search_results').append(
                '<tr><td><span class="icon icon-' + record.gang_code
                + ' ' + record.gang_code
                + '"></td><td><a tabindex="'
                + (tabindex++)
                + '" href="'
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" class="card" data-index="' + record.code
                + '">' + record.title
                + '</a></td><td class="small">'
                + record.pack + '</td></tr>');
        });
}

function handle_input_change(event) {
    DTDB.smart_filter.handler($(this).val(), update_cardsearch_result);
}

$(function () {
    $('#version-popover').popover({
        html: true
    });

    $('#card_search_form').on({
        keyup: debounce(handle_input_change, 250)
    });

});

$(document).ready(function () {
    var imageSrc = $('#img_legend').attr('src');
    if (imageSrc === "") {
        $("#decksummary").removeClass('col-sm-8').addClass('col-sm-10');
    }
});
