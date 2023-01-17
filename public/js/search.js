DTDB.data_loaded.add(function () {
    DTDB.data.sets({
        code: "alt"
    }).remove();

    DTDB.data.cards({
        pack_code: "alt"
    }).remove();

    function findMatches(q, cb) {
        if (q.match(/^\w:/)) return;
        var matches = DTDB.data.cards({title: {likenocase: q}}).map(function (record) {
            return {
                value: record.title + (record.isMultiple ? (' [' + record.pack_code  + ']') : ''),
                code: record.code,
            };
        });
        cb(matches);
    }

    $('#card').typeahead({
        hint: true,
        highlight: true,
        minLength: 3
    }, {
        name: 'cardnames',
        displayKey: 'value',
        source: findMatches
    });

    $.each(DTDB.data.cards({type_code: 'outfit'}).distinct("gang_code").sort(
        function (a, b) {
            if (b === "neutral") {
                return -1
            }
            if (a === "neutral") {
                return 1
            }
            if (b === "drifter") {
                return -1
            }
            if (a === "drifter") {
                return 1
            }
            return (a < b ? -1 : (a > b ? 1 : 0));
        }), function (index, record) {
        var example = DTDB.data.cards({"gang_code": record}).first();
        var gang = example.gang;
        var code = example.gang_code;

        var option = $('<option value="' + code + '">' + gang + '</option>');
        $('#gang').append(option);
    });
});

$(function () {
    $('#card').on('typeahead:selected typeahead:autocompleted', function (event, data) {
        var card = DTDB.data.cards({
            code: data.code
        }).first();
        var line = $('<p class="background-' + card.gang_code + '-20" style="padding: 3px 5px;border-radius: 3px;border: 1px solid silver"><button type="button" class="close" aria-hidden="true">&times;</button><input type="hidden" name="cards[]" value="' + card.code + '">' +
            card.title
            + (card.isMultiple ? (' [' + card.pack_code  + ']') : '')
            + '</p>');
        line.on({
            click: function (event) {
                line.remove()
            }
        });
        line.insertBefore($('#card'));
        $(event.target).typeahead('val', '');
    });
})
