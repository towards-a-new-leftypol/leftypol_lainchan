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
 * 	$config['additional_javascript'][] = 'js/remove-lazy-loading.js'
 *
 */


function removeLazyLoading(){
	if(document.readyState === "complete"){
		$(".post-image").removeAttr("loading")
	} else {
		$(window).on("load", removeLazyLoading)
	}
}

$(removeLazyLoading);