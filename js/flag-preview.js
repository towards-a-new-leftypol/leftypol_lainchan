/*
 * flag-preview.js - Add preview of user flag.
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/flag-preview.js';
 *   
 */

function getFlagUrl(value){
	// No flag or None flag
	if(!value || value == "") {
		return ""
	} else {
		return "/static/flags/"+value+".png"
	}
}

// Attempt to load flag (see js/save-user_flag.js)
function loadFlag () {
	var flagStorage = "flag_" + document.getElementsByName('board')[0].value;
	return window.localStorage.getItem(flagStorage);
}

function updatePreviewWithSelected(img, select) {
	img.attr("src", getFlagUrl(select.find(":selected").val()));
}

onready(function(){
	var flagImg = $('#flag_preview');
	var flagSelect = $('#user_flag');
	var loaded = loadFlag();
	flagImg.attr("src", getFlagUrl(loaded));

	flagSelect.change(function() {
		flagImg.attr("src", getFlagUrl($(this).find(":selected").val()));
	});
});

$(window).on('quick-reply', function() {
	var flagImg = $('#flag_preview');
	var quickReplyFlagImg = $('form#quick-reply img[name="flag_preview"]')
	var loaded = loadFlag();
	quickReplyFlagImg.attr("src", getFlagUrl(loaded));
	$('form#quick-reply select[name="user_flag"]').change(function() {
		updatePreviewWithSelected(quickReplyFlagImg,$(this));
		updatePreviewWithSelected(flagImg,$(this));
	});
	$('#user_flag').change(function() {
		updatePreviewWithSelected(quickReplyFlagImg,$(this));
		updatePreviewWithSelected(flagImg,$(this));
	});
});
