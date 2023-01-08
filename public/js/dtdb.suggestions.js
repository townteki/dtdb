if (typeof DTDB != "object")
    var DTDB = {
        data_loaded: jQuery.Callbacks(),
        locale: 'en'
    };
DTDB.suggestions = {};
(function (suggestions, $) {
    suggestions.codesFromindex = [];
    suggestions.matrix = [];
    suggestions.indexFromCodes = {};
    suggestions.current = [];
    suggestions.exclusions = [];
    suggestions.number = 3;

    suggestions.query = function () {
        suggestions.promise = $.ajax('/web/suggestions.json', {
            dataTYpe: 'json',
            success: function (data) {
                suggestions.codesFromindex = data.index;
                suggestions.matrix = data.matrix;
                // reconstitute the full matrix from the lower half matrix
                for (var i = 0; i < suggestions.matrix.length; i++) {
                    for (var j = i; j < suggestions.matrix.length; j++) {
                        suggestions.matrix[i][j] = suggestions.matrix[j][i];
                    }
                }
                for (var i = 0; i < suggestions.codesFromindex.length; i++) {
                    suggestions.indexFromCodes[suggestions.codesFromindex[i]] = i;
                }
            }
        });
        suggestions.promise.done(suggestions.compute);
    };

    suggestions.compute = function () {
        if (suggestions.matrix.length == 0) return;

        if (suggestions.number) {
            // init current suggestions
            suggestions.codesFromindex.forEach(function (code, index) {
                suggestions.current[index] = {
                    code: code,
                    proba: 0
                };
            });
            // find used cards
            var indexes = DTDB.data.cards({indeck: {'gt': 0}}).select('code').map(function (code) {
                return suggestions.indexFromCodes[code];
            });
            // add suggestions of all used cards
            indexes.forEach(function (i) {
                if (suggestions.matrix[i]) {
                    suggestions.matrix[i].forEach(function (value, j) {
                        suggestions.current[j].proba += (value || 0);
                    });
                }
            });
            // remove suggestions of already used cards
            indexes.forEach(function (i) {
                if (suggestions.current[i]) suggestions.current[i].proba = 0;
            });
            // remove suggestions of outfit
            DTDB.data.cards({type_code: 'outfit'}).select('code').map(function (code) {
                return suggestions.indexFromCodes[code];
            }).forEach(function (i) {
                if (suggestions.current[i]) suggestions.current[i].proba = 0;
            });
            // remove suggestions of excluded cards
            suggestions.exclusions.forEach(function (i) {
                if (suggestions.current[i]) suggestions.current[i].proba = 0;
            });
            // sort suggestions
            suggestions.current.sort(function (a, b) {
                return (b.proba - a.proba);
            });
        }
        suggestions.show();
    };

    suggestions.show = function () {
        var table = $('#table-suggestions');
        var tbody = table.children('tbody');
        tbody.empty();
        if (!suggestions.number && table.is(':visible')) {
            table.hide();
            return;
        }
        if (suggestions.number && !table.is(':visible')) {
            table.show();
        }
        var nb = 0;
        for (var i = 0; i < suggestions.current.length; i++) {
            var card = DTDB.data.cards({code: suggestions.current[i].code}).first();
            if (is_card_usable(card) && Filters.pack_code.indexOf(card.pack_code) > -1) {
                var div = suggestions.div(card);
                div.on('click', 'button.close', suggestions.exclude.bind(this, card.code));
                tbody.append(div);
                if (++nb == suggestions.number) break;
            }
        }
    };

    suggestions.div = function (record) {
        var radios = '';
        for (var i = 0; i <= record.maxqty; i++) {
            radios += '<label class="btn btn-xs btn-default'
                + (i == record.indeck ? ' active' : '')
                + '"><input type="radio" name="qty-' + record.code
                + '" value="' + i + '">' + i + '</label>';
        }

        var div = $('<tr class="card-container" data-index="'
            + record.code
            + '"><td><button type="button" class="close"><span aria-hidden="true">&times;</span><span class="sr-only">Remove</span></button></td>'
            + '<td><div class="btn-group" data-toggle="buttons">'
            + radios
            + '</div></td><td>' + DTDB.format.face(record) + ' <a class="card" href="'
            + Routing.generate('cards_zoom', {card_code: record.code})
            + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
            + record.title
            + (record.isMultiple ? (' [' + record.pack_code  + ']') : '')
            + '</a></td></tr>');

        return div;
    }

    suggestions.exclude = function (code) {
        suggestions.exclusions.push(suggestions.indexFromCodes[code]);
        suggestions.compute();
    }

    suggestions.pick = function (event) {
        InputByTitle = false;
        var input = this;
        $(input).closest('tr').animate({
            opacity: 0
        }, function () {
            handle_quantity_change.call(input, event);
        });
    }

    $(function () {
        suggestions.query();

        $('#table-suggestions').on({
            change: suggestions.pick
        }, 'input[type=radio]');

    })

})(DTDB.suggestions, jQuery);


