/*
 * webp-fix.js
 *
 *	Detects lack of webp support, loads full images instead. 
 *
 * Usage:
 *   $config['additional_javascript'][] = 'js/jquery.min.js';
 *   $config['additional_javascript'][] = 'js/webp-fix.js';
 *
 */

$(document).ready(function() {

	// from: https://stackoverflow.com/a/54120785
	function testWebP() {
		return new Promise(res => {
			const webP = new Image();
			webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
			webP.onload = webP.onerror = () => {
				res(webP.height === 2);
			};        
		})
	};

	testWebP().then(hasWebP => {
		if(!hasWebP){
			$('div.file a').each(function(index){
				let ext = ["png","jpg", "jpeg"];
				let file = $(this);
				let fullSize = file.prop("href");
				if (ext.includes(fullSize.split('.').pop())){
					file.find('img').prop("src", fullSize);
			  	}
			});
		}
	});
}