/*
 * remove-lazy-loading.js - Removes lazy loading from thread reply images to force them to eagerly load. 
 * Lazy loading of images helps render threads quicker, but this means that images will only be loaded on scroll.
 * This impacts the user experience in a negative way since they will often see blank images when scrolling 
 * while the thumbnails load. This js solves this problem by allowing images to deferred and loaded lazily, 
 * and once everything has loaded, eagerly load the images at once by removing the lazy attribute.
 *
 *
 * Copyleft (ɔ) 2021 Leftypol Moderation Team
 *
 * Usage:
 *	// if deferred js is implemented and enabled:
 *	// (see: https://github.com/towards-a-new-leftypol/leftypol_lainchan/pull/308/commits/dca55a16431591b3e8615320e7b78ee3e0e747b7 ):
 *   $config['additional_javascript_defer'][] = 'js/webm-settings.js';
 *	// otherwise:
 * 	$config['additional_javascript'][] = 'js/style-select.js'
 *
 */


function removeLazyLoading(){
	if(document.readyState === "complete"){
		setTimeOut(
			() => $(".post-image").removeAttr("loading")
			, 500)
	} else {
		$(window).on("load", removeLazyLoading)
	}
}

$(removeLazyLoading);