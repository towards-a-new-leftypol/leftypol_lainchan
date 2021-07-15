/*
 * options/general.js - general settings tab for options panel
 *
 * Copyright (c) 2014 Marcin Łabanowski <marcin@6irc.net>
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/options.js';
 *   $config['additional_javascript'][] = 'js/style-select.js';
 *   $config['additional_javascript'][] = 'js/options/general.js';
 */

+function(){

function styleShitChoicer() {
    var savedChoice = localStorage.stylesheet;

    var e_select = document.createElement("select");
    e_select.name = "opt-style-select";

    for (var i=0; i < styles.length; i++) {
        var styleName = styles[i][0];
        var e_option = document.createElement("option");
        e_option.innerHTML = styleName;
        e_option.value = styleName;

        if (styleName == savedChoice) {
            e_option.selected = true;
        }

        e_select.appendChild(e_option);
    }

    e_select.addEventListener('change', function(e) {
        changeStyle(e.target.value);
    });

    return e_select;
}

function appendStyleSelectorToTab(tab_content) {
    var div = document.createElement('div');
    div.className = "options_general_tab--select_opt";

    var label = document.createElement('span');
    label.innerHTML = "Theme: ";
    div.appendChild(label);
    div.appendChild(styleShitChoicer());
    $(div).appendTo(tab_content);
}

var tab = Options.add_tab("general", "home", _("General"));

$(function(){
    var stor = $("<div>"+_("Storage: ")+"</div>");
    stor.appendTo(tab.content);

    $("<button>"+_("Export")+"</button>").appendTo(stor).on("click", function() {
        var str = JSON.stringify(localStorage);

        $(".output").remove();
        $("<input type='text' class='output'>").appendTo(stor).val(str);
    });

    $("<button>"+_("Import")+"</button>").appendTo(stor).on("click", function() {
        var str = prompt(_("Paste your storage data"));
        if (!str) return false;
        var obj = JSON.parse(str);
        if (!obj) return false;

        localStorage.clear();
        for (var i in obj) {
            localStorage[i] = obj[i];
        }

        document.location.reload();
    });

    $("<button>"+_("Erase")+"</button>").appendTo(stor).on("click", function() {
        if (confirm(_("Are you sure you want to erase your storage? This involves your hidden threads, watched threads, post password and many more."))) {
            localStorage.clear();
            document.location.reload();
        }
    });

    appendStyleSelectorToTab(tab.content);
});

}();
