var InputByTitle = false;
var DisplayColumns = 1;
var BaseSets = 2;
var WweSets = 2;
var Buttons_Behavior = 'cumulative';
var Snapshots = []; // deck contents autosaved
var Autosave_timer = null;
var Deck_changed_since_last_autosave = false;
var Autosave_running = false;
var Autosave_period = 60;
var RankNames = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];

DTDB.data_loaded.add(function () {
    var localStorageDisplayColumns;
    if (localStorage
        && (localStorageDisplayColumns = parseInt(localStorage
            .getItem('display_columns'), 10)) !== null
        && [1, 2, 3].indexOf(localStorageDisplayColumns) > -1) {
        DisplayColumns = localStorageDisplayColumns;
    }
    $('input[name=display-column-' + DisplayColumns + ']')
        .prop('checked', true);

    var localStorageBaseSets;
    if (localStorage
        && (localStorageBaseSets = parseInt(localStorage
            .getItem('base_sets'), 10)) !== null
        && [1, 2].indexOf(localStorageBaseSets) > -1) {
        BaseSets = localStorageBaseSets;
    }
    $('input[name=base-set-' + BaseSets + ']').prop('checked', true);

    var localStorageWweSets;
    if (localStorage
        && (localStorageWweSets = parseInt(localStorage
            .getItem('wwe_sets'), 10)) !== null
        && [1, 2].indexOf(localStorageWweSets) > -1) {
        WweSets = localStorageWweSets;
    }
    $('input[name=weird-west-' + WweSets + ']').prop('checked', true);

    var localStorageSuggestions;
    if (localStorage
        && (localStorageSuggestions = parseInt(localStorage
            .getItem('show_suggestions'), 10)) !== null
        && [0, 3, 10].indexOf(localStorageSuggestions) > -1) {
        DTDB.suggestions.number = localStorageSuggestions;
    }
    $('input[name=show-suggestions-' + DTDB.suggestions.number + ']').prop('checked', true);

    var localStorageButtonsBehavior;
    if (localStorage
        && (localStorageButtonsBehavior = localStorage.getItem('buttons_behavior')) !== null
        && ['cumulative', 'exclusive'].indexOf(localStorageButtonsBehavior) > -1) {
        Buttons_Behavior = localStorageButtonsBehavior;
    }
    $('input[name=buttons-behavior-' + Buttons_Behavior + ']').prop('checked', true);

    DTDB.data.sets({
        code: "alt"
    }).remove();

    DTDB.data.cards({
        pack_code: "alt"
    }).remove();
    var sets_in_deck = {};
    DTDB.data.cards().each(function (record) {
        var indeck = 0, start = 0;
        if (Deck[record.code]) {
            indeck = parseInt(Deck[record.code].quantity, 10);
            start = Deck[record.code].start;
            sets_in_deck[record.pack_code] = 1;
        }
        DTDB.data.cards(record.___id).update({
            indeck: indeck,
            start: start
        });
    });
    update_deck();
    DTDB.data.cards().each(function (record) {
        var max_qty;
        if (record.type_code == "outfit")
            max_qty = 1;
        else if (record.pack_code == 'DTR')
            max_qty = Math.min(record.quantity * BaseSets, 4);
        else if (record.pack_code == 'DT2')
            max_qty = Math.min(record.quantity * WweSets, 4);
        else
            max_qty = record.quantity;
        DTDB.data.cards(record.___id).update({
            maxqty: max_qty
        });
    });

    if (Modernizr.touch) {
        $('#gang_code, #suit, #rank').css('width', '100%').addClass('btn-group-vertical');
    } else {
        $('#gang_code, #suit, #rank').addClass('btn-group');
    }
    $('#gang_code').empty();
    $.each(DTDB.data.cards().distinct("gang_code").sort(
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
        var label = $('<label class="btn btn-default btn-sm" data-code="' + record
            + '" title="' + gang + '"><input type="checkbox" name="' + record
            + '"><img src="'
            + Url_GangImage.replace('xxx', record)
            + '" style="width:14px"></label>');
        if (Modernizr.touch) {
            label.append(' ' + gang);
            label.addClass('btn-block');
        } else {
            label.tooltip({container: 'body'});
        }
        $('#gang_code').append(label);
    });
    $('#gang_code').button();
    $('#gang_code').children('label').each(function (index, elt) {
        $(elt).button('toggle');
    });

    $('#suit').empty();
    $.each(DTDB.data.cards().distinct("suit").sort(), function (index, record) {
        var suit = record;
        if (suit === null) suit = "None";
        var label = $('<label class="btn btn-default btn-sm" data-code="'
            + record + '" title="' + suit + '"><input type="checkbox" name="' + record
            + '">&' + (record === null ? '#8962' : record.toLowerCase()) + ';</label>');
        if (Modernizr.touch) {
            label.append(' ' + record);
            label.addClass('btn-block');
        } else {
            label.tooltip({container: 'body'});
        }
        $('#suit').append(label);
    });
    $('#suit').button();
    $('#suit').children('label').each(function (index, elt) {
        $(elt).button('toggle');
    });
    $('#suit').children('label:last-child').each(function (index, elt) {
        $(elt).button('toggle');
    });

    $('#pack_code').empty();
    DTDB.data.sets().each(function (record) {
        var checked = record.available === "" && sets_in_deck[record.code] === null ? '' : ' checked="checked"';
        $('#pack_code').append(
            '<li><a href="#"><label><input type="checkbox" name="'
            + record.code + '"' + checked + '>'
            + record.name + '</label></a></li>'
        );
    });
    $('#rank').empty();

    var label = $('<label class="btn btn-default btn-sm" data-code="null" title="-"><input type="checkbox" name="null">-</label>');
    if (Modernizr.touch) {
        label.addClass('btn-block');
    } else {
        label.tooltip({container: 'body'});
    }
    $('#rank').append(label);

    RankNames.forEach(function (element, index, array) {
        label = $('<label class="btn btn-default btn-sm" data-code="' + (index + 1)
            + '" title="' + element + '"><input type="checkbox" name="' + (index + 1)
            + '">'
            + element
            + '</label>');
        if (Modernizr.touch) {
            label.addClass('btn-block');
        } else {
            label.tooltip({container: 'body'});
        }
        $('#rank').append(label);

    });
    $('#rank').button();
    /*
    $('#rank').children('label').each(function(index, elt) {
        $(elt).button('toggle');
    });
    */

    $('input[name=Outfit]').prop("checked", false);

    $('.filter')
        .each(
            function (index, div) {
                var columnName = $(div).attr('id');
                var arr = [], checked;
                $(div)
                    .find("input[type=checkbox]")
                    .each(
                        function (index, elt) {
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
    $.each(Filters, function (k) {
        if (Filters[k] != '') {
            FilterQuery[k] = Filters[k];
        }
    });

    refresh_collection();

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

    $('#filter-text').typeahead({
        hint: true,
        highlight: true,
        minLength: 2
    }, {
        name: 'cardnames',
        displayKey: 'value',
        source: findMatches
    });

    $('html,body').css('height', 'auto');
    $('.container').show();
});

function uncheck_all_others() {
    $(this).closest(".filter").find("input[type=checkbox]").prop("checked", false);
    $(this).children('input[type=checkbox]').prop("checked", true).trigger('change');
}

function check_all_others() {
    $(this).closest(".filter").find("input[type=checkbox]").prop("checked", true);
    $(this).children('input[type=checkbox]').prop("checked", false);
}

function uncheck_all_active() {
    $(this).closest(".filter").find("label.active").button('toggle');
}

function check_all_inactive() {
    $(this).closest(".filter").find("label:not(.active)").button('toggle');
}

$(function () {
    $('html,body').css('height', '100%');

    $('#filter-text').on('typeahead:selected typeahead:autocompleted',
        DTDB.card_modal.typeahead);

    $(document).on('hidden.bs.modal', function (event) {
        if (InputByTitle) {
            setTimeout(function () {
                $('#filter-text').typeahead('val', '').focus();
            }, 100);
        }
    });

    $('#pack_code,.search-buttons').on({
        change: handle_input_change,
        click: function (event) {
            var dropdown = $(this).closest('ul').hasClass('dropdown-menu');
            if (dropdown) {
                if (event.shiftKey) {
                    if (!event.altKey) {
                        uncheck_all_others.call(this);
                    } else {
                        check_all_others.call(this);
                    }
                }
                event.stopPropagation();
            } else {
                if (!event.shiftKey && Buttons_Behavior === 'exclusive' || event.shiftKey && Buttons_Behavior === 'cumulative') {
                    if (!event.altKey) {
                        uncheck_all_active.call(this);
                    } else {
                        check_all_inactive.call(this);
                    }
                }
            }
        }
    }, 'label');

    $('#filter-text').on({
        input: function (event) {
            var q = $(this).val();
            if (q.match(/^\w[:<>!]/)) DTDB.smart_filter.handler(q, refresh_collection);
            else DTDB.smart_filter.handler('', refresh_collection);
        }
    });

    $('#save_form').submit(handle_submit);

    $('#btn-save-as-copy').on('click', function (event) {
        $('#deck-save-as-copy').val(1);
    });
    $('#btn-cancel-edits').on('click', function (event) {
        var edits = $.grep(Snapshots, function (snapshot) {
            return snapshot.saved === false;
        });
        if (edits.length) {
            var confirmation = confirm("This operation will revert the changes made to the deck since " + edits[edits.length - 1].datecreation.calendar() + ". The last " + (edits.length > 1 ? edits.length + " edits" : "edit") + " will be lost. Do you confirm?");
            if (!confirmation) return false;
        }
        $('#deck-cancel-edits').val(1);
    });

    $('#collection').on({
        change: function (event) {
            InputByTitle = false;
            handle_quantity_change.call(this, event);
        }
    }, 'input[type=radio]');
    $('#collection').on({
        click: function (event) {
            InputByTitle = false;
        }
    }, 'a.card');
    $('.modal-qty').on({
        change: handle_quantity_change
    }, 'input[type=radio]');
    $('.modal').on({
        change: handle_qty_start_change
    }, 'input[name=start]');
    $('.modal').on({
        change: handle_start_change
    }, 'input[type=checkbox]');
    $('input[name=show-disabled]').on({
        change: function (event) {
            HideDisabled = !$(this).prop('checked');
            refresh_collection();
        }
    });
    $('input[name=only-deck]').on({
        change: function (event) {
            ShowOnlyDeck = $(this).prop('checked');
            refresh_collection();
        }
    });
    $('input[name=display-column-1]').on({
        change: function (event) {
            $('input[name=display-column-2]').prop('checked', false);
            $('input[name=display-column-3]').prop('checked', false);
            DisplayColumns = 1;
            if (localStorage)
                localStorage.setItem('display_columns', DisplayColumns);
            refresh_collection();
        }
    });
    $('input[name=display-column-2]').on({
        change: function (event) {
            $('input[name=display-column-1]').prop('checked', false);
            $('input[name=display-column-3]').prop('checked', false);
            DisplayColumns = 2;
            if (localStorage)
                localStorage.setItem('display_columns', DisplayColumns);
            refresh_collection();
        }
    });
    $('input[name=display-column-3]').on({
        change: function (event) {
            $('input[name=display-column-1]').prop('checked', false);
            $('input[name=display-column-2]').prop('checked', false);
            DisplayColumns = 3;
            if (localStorage)
                localStorage.setItem('display_columns', DisplayColumns);
            refresh_collection();
        }
    });
    $('input[name=base-set-1]').on({
        change: function (event) {
            $('input[name=base-set-2]').prop('checked', false);
            BaseSets = 1;
            if (localStorage)
                localStorage.setItem('base_sets', BaseSets);
            update_base_sets('DTR');
            refresh_collection();
        }
    });
    $('input[name=base-set-2]').on({
        change: function (event) {
            $('input[name=base-set-1]').prop('checked', false);
            BaseSets = 2;
            if (localStorage)
                localStorage.setItem('base_sets', BaseSets);
            update_base_sets('DTR');
            refresh_collection();
        }
    });
    $('input[name=weird-west-1]').on({
        change: function (event) {
            $('input[name=weird-west-2]').prop('checked', false);
            WweSets = 1;
            if (localStorage)
                localStorage.setItem('wwe_sets', WweSets);
            update_base_sets('DT2');
            refresh_collection();
        }
    });
    $('input[name=weird-west-2]').on({
        change: function (event) {
            $('input[name=weird-west-1]').prop('checked', false);
            WweSets = 2;
            if (localStorage)
                localStorage.setItem('wwe_sets', WweSets);
            update_base_sets('DT2');
            refresh_collection();
        }
    });
    $('input[name=show-suggestions-0]').on({
        change: function (event) {
            $('input[name=show-suggestions-3]').prop('checked', false);
            $('input[name=show-suggestions-10]').prop('checked', false);
            DTDB.suggestions.number = 0;
            if (localStorage)
                localStorage.setItem('show_suggestions', DTDB.suggestions.number);
            DTDB.suggestions.show();
        }
    });
    $('input[name=show-suggestions-3]').on({
        change: function (event) {
            $('input[name=show-suggestions-0]').prop('checked', false);
            $('input[name=show-suggestions-10]').prop('checked', false);
            DTDB.suggestions.number = 3;
            if (localStorage)
                localStorage.setItem('show_suggestions', DTDB.suggestions.number);
            DTDB.suggestions.show();
        }
    });
    $('input[name=show-suggestions-10]').on({
        change: function (event) {
            $('input[name=show-suggestions-0]').prop('checked', false);
            $('input[name=show-suggestions-3]').prop('checked', false);
            DTDB.suggestions.number = 10;
            if (localStorage)
                localStorage.setItem('show_suggestions', DTDB.suggestions.number);
            DTDB.suggestions.show();
        }
    });
    $('input[name=buttons-behavior-cumulative]').on({
        change: function (event) {
            $('input[name=buttons-behavior-exclusive]').prop('checked', false);
            $('input[name=buttons-behavior-exclusive]').prop('checked', false);
            Buttons_Behavior = 'cumulative';
            if (localStorage)
                localStorage.setItem('buttons_behavior', Buttons_Behavior);
        }
    });
    $('input[name=buttons-behavior-exclusive]').on({
        change: function (event) {
            $('input[name=buttons-behavior-cumulative]').prop('checked', false);
            $('input[name=buttons-behavior-cumulative]').prop('checked', false);
            Buttons_Behavior = 'exclusive';
            if (localStorage)
                localStorage.setItem('buttons_behavior', Buttons_Behavior);
        }
    });
    $('thead').on({
        click: handle_header_click
    }, 'a[data-sort]');
    $('#cardModal').on({
        keypress: function (event) {
            var num = parseInt(event.which, 10) - 48;
            $('.modal input[type=radio][value=' + num + ']').trigger('change');
        }
    });
    $('#filter-text-button')
        .tooltip(
            {
                html: true,
                container: 'body',
                placement: 'bottom',
                trigger: 'click',
                title: "<h5>Smart filter syntax</h5><ul style=\"text-align:left\">" +
                    "<li>by default, filters on title</li></ul>" +
                    "<h5>Criteria</h5>" +
                    "<ul style=\"text-align:left\"><li>x: card text</li>" +
                    "<li>e: expansion name</li>" +
                    "<li>t: card type</li>" +
                    "<li>k: keyword</li>" +
                    "<li>g: gang (r, e, l, s, f, m, -)</li>" +
                    "<li>s: bullet type (stud, draw, bonus)</li>" +
                    "<li>r: cost</li>" +
                    "<li>v: value</li>" +
                    "<li>u: upkeep</li>" +
                    "<li>p: production</li>" +
                    "<li>b: bullet value</li>" +
                    "<li>i: influence</li>" +
                    "<li>c: control</li>" +
                    "<li>w: wealth, starting stash</li>" +
                    "<li>f: flavor text</li>" +
                    "<li>a: artist name</li>" +
                    "<li>(e.g. k:\"mad scientist 2\" i>0)</li></ul>" +
                    "<h5>Operator</h5>" +
                    "<ul style=\"text-align:left\"><li>: &ndash; equals/includes</li>" +
                    "<li>! &ndash; different from</li>" +
                    "<li>< &ndash; less than</li>" +
                    "<li>> &ndash; more than</li></ul>"
            });

    var converter = new Markdown.Converter();
    $('#description').on(
        'keyup',
        function () {
            $('#description-preview').html(
                converter.makeHtml($('#description').val()));
        });

    $('#description').textcomplete(
        [
            {
                match: /\B#([\-+\w]*)$/,
                search: function (term, callback) {
                    callback(DTDB.data.cards({
                        title: {
                            likenocase: term
                        }
                    }).get());
                },
                template: function (value) {
                    return value.title;
                },
                replace: function (value) {
                    return '[' + value.title + ']('
                        + Routing.generate('cards_zoom', {card_code: value.code})
                        + ')';
                },
                index: 1
            }
        ]);

    $('#tbody-history').on('click', 'a[role=button]', load_snapshot);
    $.each(History, function (index, snapshot) {
        add_snapshot(snapshot);
    });

    $('#menu-sort').on({
        change: function (event) {
            if ($(this).attr('id').match(/btn-sort-(\w+)/)) {
                DisplaySort = RegExp.$1;
                update_deck();
            }
        }
    }, 'a');

    setInterval(autosave_interval, 1000);
});

function autosave_interval() {
    if (Autosave_running) return;
    if (Autosave_timer < 0 && Deck_id) Autosave_timer = Autosave_period;
    //('#tab-header-history').html('History '+Autosave_timer);
    $('#history-timer-bar').css('width', (Autosave_timer * 100 / Autosave_period) + '%').attr('aria-valuenow', Autosave_timer).find('span').text(Autosave_timer + ' seconds remaining.');
    if (Autosave_timer === 0) {
        deck_autosave();
    }
    Autosave_timer--;
}

// if diff is undefined, consider it is the content at load
function add_snapshot(snapshot) {
    snapshot.datecreation = snapshot.datecreation ? moment(snapshot.datecreation) : moment();
    Snapshots.push(snapshot);

    var list = [];
    if (snapshot.variation) {
        $.each(snapshot.variation[0], function (code, qty) {
            var card = DTDB.data.cards({code: code}).first();
            if (!card) return;
            list.push(
                '+' + qty + ' '
                + '<a href="' + Routing.generate('cards_zoom', {card_code: code}) + '" class="card" data-index="' + code + '">'
                + card.title
                + (card.isMultiple ? (' [' + card.pack_code  + ']') : '')
                + '</a>'
            );
        });
        $.each(snapshot.variation[1], function (code, qty) {
            var card = DTDB.data.cards({code: code}).first();
            if (!card) return;
            list.push(
                '&minus;' + qty + ' '
                + '<a href="' + Routing.generate('cards_zoom', {card_code: code}) + '" class="card" data-index="' + code + '">'
                + card.title
                + (card.isMultiple ? (' [' + card.pack_code  + ']') : '')
                + '</a>'
            );
        });
    } else {
        list.push("First version");
    }

    $('#tbody-history').prepend('<tr' + (snapshot.saved ? '' : ' class="warning"') + '><td>' + snapshot.datecreation.calendar() + (snapshot.saved ? '' : ' (unsaved)') + '</td><td>' + list.join('<br>') + '</td><td><a role="button" href="#" data-index="' + (Snapshots.length - 1) + '"">Revert</a></td></tr>');

    Autosave_timer = -1; // start timer
}

function load_snapshot(event) {
    var index = $(this).data('index');
    var snapshot = Snapshots[index];
    if (!snapshot) return;

    DTDB.data.cards().each(function (record) {
        var indeck = 0;
        if (snapshot.content[record.code]) {
            indeck = parseInt(snapshot.content[record.code], 10);
        }
        DTDB.data.cards(record.___id).update({
            indeck: indeck
        });
    });
    update_deck();
    refresh_collection();
    DTDB.suggestions.compute();
    Deck_changed_since_last_autosave = true;
    return false;
}

function deck_autosave() {
    // check if deck has been modified since last autosave
    if (!Deck_changed_since_last_autosave || !Deck_id) return;
    // compute diff between last snapshot and current deck
    var last_snapshot = Snapshots[Snapshots.length - 1].content;
    console.log('last_snapshot', last_snapshot);
    var current_deck = get_deck_content();
    Deck_changed_since_last_autosave = false;
    var r = DTDB.diff.compute_simple([current_deck, last_snapshot]);
    if (!r) return;
    var diff = JSON.stringify(r[0]);
    if (diff == '[{},{}]') return;
    // send diff to autosave
    $('#tab-header-history').html("Autosave...");
    Autosave_running = true;
    $.ajax(Routing.generate('deck_autosave', {deck_id: Deck_id}), {
        data: {diff: diff},
        type: 'POST',
        success: function (data, textStatus, jqXHR) {
            add_snapshot({datecreation: data, variation: r[0], content: current_deck, saved: false});
        },
        error: function (jqXHR, textStatus, errorThrown) {
            Deck_changed_since_last_autosave = true;
        },
        complete: function () {
            $('#tab-header-history').html("History");
            Autosave_running = false;
        }
    });
}

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
    div.find("input[type=checkbox]").each(function (index, elt) {
        var value = $(elt).attr('name');
        if (value === "null") value = null;
        if ($(elt).prop('checked')) {
            if (columnName == "rank") value = parseInt(value);
            arr.push(value);
        }
        if (columnName == "pack_code" && localStorage) {
            localStorage.setItem('pack_code_' + value, $(elt).prop('checked') ? "on" : "off");
        }
    });
    Filters[columnName] = arr;

    FilterQuery = {};
    $.each(Filters, function (k, v) {
        if (v && v.length) {
            FilterQuery[k] = v;
        }
    });

    refresh_collection();
}

function get_deck_content() {
    var deck_content = {};
    DTDB.data.cards({
        indeck: {
            'gt': 0
        }
    }).each(function (record) {
        deck_content[record.code] = record.indeck;
    });
    return deck_content;
}

function get_deck_full_content() {
    var deck_content = {};
    DTDB.data.cards({
        indeck: {
            'gt': 0
        }
    }).each(function (record) {
        deck_content[record.code] = {quantity: record.indeck, start: record.start};
    });
    return deck_content;
}

function handle_submit(event) {
    var deck_json = JSON.stringify(get_deck_full_content());
    $('input[name=content]').val(deck_json);
    $('input[name=description]').val($('textarea[name=description_]').val());
    $('input[name=tags]').val($('input[name=tags_]').val());
}

function handle_start_change(event) {
    var index = $(this).closest('.card-container').data('index') || $(this).closest('div.modal').data('index');
    var start = $(this).prop('checked');
    var card = DTDB.data.cards({code: index}).first();
    var indeck = card.indeck;
    if (start && indeck == 0) indeck = 1;
    DTDB.data.cards({
        code: index
    }).update({
        start: start ? 1 : 0,
        indeck: indeck
    });
    refresh_collection();
    $('div.modal').modal('hide');
    update_deck();
}

function handle_qty_start_change(event) {
    var index = $(this).closest('.card-container').data('index') || $(this).closest('div.modal').data('index');
    var start = parseInt($(this).val(), 10);
    var card = DTDB.data.cards({code: index}).first();
    var indeck = card.indeck;
    if (indeck < start) indeck = start;
    DTDB.data.cards({
        code: index
    }).update({
        start: start,
        indeck: indeck
    });
    refresh_collection();
    $('div.modal').modal('hide');
    update_deck();
}

function handle_quantity_change(event) {
    var index = $(this).closest('.card-container').data('index')
        || $(this).closest('div.modal').data('index');
    var in_collection = $(this).closest('#collection').size();
    var quantity = parseInt($(this).val(), 10);
    $(this).closest('.card-container')[quantity ? "addClass" : "removeClass"]('in-deck');
    var card = DTDB.data.cards({code: index}).first();
    var newdata = {indeck: quantity};
    if (quantity < card.start) newdata.start = quantity;
    DTDB.data.cards({code: index}).update(newdata);
    if (card.type_code == "outfit") {
        DTDB.data.cards({
            indeck: {
                'gt': 0
            },
            type_code: 'outfit',
            code: {
                '!==': index
            }
        }).update({
            indeck: 0
        });
    }
    update_deck();
    if (card.type_code == "outfit") {
        $.each(CardDivs, function (nbcols, rows) {
            if (rows)
                $.each(rows, function (index, row) {
                    row.removeClass("disabled").find('label').removeClass(
                        "disabled").find('input[type=radio]').attr(
                        "disabled", false);
                });
        });
        refresh_collection();
    } else {
        $.each(CardDivs, function (nbcols, rows) {
            // rows is an array of card rows
            if (rows && rows[index]) {
                // rows[index] is the card row of our card
                rows[index].find('input[name="qty-' + index + '"]').each(
                    function (i, element) {
                        if ($(element).val() != quantity) {
                            $(element).prop('checked', false).closest(
                                'label').removeClass('active');
                        } else {
                            if (!in_collection) {
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
        $('#filter-text').typeahead('val', '').focus().blur();

    Deck_changed_since_last_autosave = true;
}

function update_base_sets(pack_code) {
    CardDivs = [null, {}, {}, {}];
    DTDB.data.cards({
        pack_code: pack_code
    }).each(function (record) {
        var quantity = pack_code === 'DTR' ? record.quantity * BaseSets : record.quantity * WweSets;
        var max_qty = Math.min(quantity, 4);
        if (record.type_code == "outfit" || record.limited)
            max_qty = 1;
        DTDB.data.cards(record.___id).update({
            maxqty: max_qty
        });
    });
}

function build_div(record) {
    var radios = '';
    if (record.keywords == null || record.keywords.search('Token') == -1) {
        for (var i = 0; i <= record.maxqty; i++) {
            radios += '<label class="btn btn-xs btn-default'
                + (i == record.indeck ? ' active' : '')
                + '"><input type="radio" name="qty-' + record.code
                + '" value="' + i + '">' + i + '</label>';
        }
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
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
                + record.title
                + (record.isMultiple ? (' [' + record.pack_code  + ']') : '')
                + '</a></td><td class="value-' + record.rank
                + '">' + DTDB.format.rank(record) + '</td><td class="suit" title="' + record.suit
                + '">' + DTDB.format.suit(record) + '</td><td class="gang" title="' + record.gang + '">'
                + imgsrc + '</td></tr>');
            break;

        case 2:

            div = $('<div class="col-sm-6 card-container" data-index="'
                + record.code
                + '">'
                + '<div class="media">'
                + '<div class="media-left"><a class="pull-left card" href="'
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
                + '    <img class="media-object" src="' + record.imagesrc + '">'
                + '</a></div>'
                + '<div class="media-body">'
                + '    <h4 class="media-heading"><a class="card" href="'
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
                + record.title
                + (record.isMultiple ? (' [' + record.pack_code  + ']') : '')
                + '</a></h4>'
                + '    <div class="btn-group" data-toggle="buttons">' + radios
                + '</div>' + '</div>' + '</div>' + '</div>');
            break;

        case 3:

            div = $('<div class="col-sm-4 card-container" data-index="'
                + record.code
                + '">'
                + '<div class="media">'
                + '<div class="media-left"><a class="pull-left card" href="'
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
                + '    <img class="media-object" src="' + record.imagesrc + '">'
                + '</a></div>'
                + '<div class="media-body">'
                + '    <h5 class="media-heading"><a class="card" href="'
                + Routing.generate('cards_zoom', {card_code: record.code})
                + '" data-target="#cardModal" data-remote="false" data-toggle="modal">'
                + record.title
                + (record.isMultiple ? (' [' + record.pack_code  + ']') : '')
                + '</a></h5>'
                + '    <div class="btn-group" data-toggle="buttons">' + radios
                + '</div>' + '</div>' + '</div>' + '</div>');
            break;

    }

    return div;
}

function is_card_usable(record) {
    if (record.keywords != null && record.keywords.search('Token') != -1) return false
    return true;
}

function update_filtered() {
    $('#collection-table').empty();
    $('#collection-grid').empty();

    var sortType = {
        'rank': ['asec', 'desc'],
        'suit': ['asec', 'desc'],
        'title': ['asec', 'desc'],
        'gang': ['asec', 'desc'],
        'indeck': ['asec', 'desc']
    };
    var order = sortType[Sort][Order > 0 ? 0 : 1];
    var counter = 0, container = $('#collection-table');
    var SmartFilterQuery = DTDB.smart_filter.get_query(FilterQuery);
    DTDB.data.cards(SmartFilterQuery)
        .order(Sort + ' ' + order + ',title')
        .each(
            function (record) {

                if (ShowOnlyDeck && !record.indeck)
                    return;

                var unusable = !is_card_usable(record);

                if (HideDisabled && unusable)
                    return;

                var index = record.code;
                var row = (CardDivs[DisplayColumns][index] || (CardDivs[DisplayColumns][index] = build_div(record)))
                    .data("index", record.code);
                row.find('input[name="qty-' + record.code + '"]').each(
                    function (i, element) {
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
