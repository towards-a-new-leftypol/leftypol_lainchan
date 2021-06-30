/*
 * style-select.js
 * https://github.com/savetheinternet/Tinyboard/blob/master/js/style-select.js
 *
 * Changes the stylesheet chooser links to a <select>
 *
 * Released under the MIT license
 * Copyright (c) 2013 Michael Save <savetheinternet@tinyboard.org>
 * Copyright (c) 2013-2014 Marcin Łabanowski <marcin@6irc.net> 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/style-select.js';
 *
 */

$(document).ready(function () {
	var stylesDiv = $('div.styles');
	var pages = $('div.pages');
	var stylesSelect = $('<select></select>').css({float:"none"});
	var options = [];
	
	var i = 1;
	stylesDiv.children().each(function() {
		var name = this.innerHTML.replace(/(^\[|\]$)/g, '');
		var opt = $('<option></option>')
			.html(name)
			.val(i);
		if ($(this).hasClass('selected'))
			opt.attr('selected', true);
		options.push ([name.toUpperCase (), opt]);
		$(this).attr('id', 'style-select-' + i);
		i++;
	});

	options.sort ((a, b) => {
		const keya = a [0];
		const keyb = b [0];
		if (keya < keyb) { return -1; }
		if (keya > keyb) { return  1; }
		return 0;
	}).forEach (([key, opt]) => {
		stylesSelect.append(opt);
	});
	
	stylesSelect.change(function() {
		$('#style-select-' + $(this).val()).click();
	});
	
	stylesDiv.hide()	
	pages.after(
		$('<div id="style-select"></div>')
			.append(_('Select theme: '), stylesSelect)
	);
});
stylesDiv = $('div.styles');
	var pages = $('div.pages');
	var stylesSelect = $('<select></select>');
	
	var i = 1;
	stylesDiv.children().each(function() {
		var opt = $('<option></option>')
			.html(this.innerHTML.replace(/(^\[|\]$)/g, ''))
			.val(i);
		if ($(this).hasClass('selected'))
			opt.attr('selected', true);
		stylesSelect.append(opt);
		$(this).attr('id', 'style-select-' + i);
		i++;
	});
	
	stylesSelect.change(function() {
		$('#style-select-' + $(this).val()).click();
	});
	
	stylesDiv.hide()	
	pages.after(
		$('<div id="style-select"></div>')
			/*.text(_('Style: '))*/
			.append(stylesSelect)
	);
});


