SiteKit.xhrOptions.swapContent = function(container, oldContent, newContent, direction) {
	
	var animDuration = 200;
	
	// Fade out old content
	oldContent.fadeOut({
		duration: animDuration,
		complete: function() {
			
			// Fade in new content
			newContent.fadeIn({
				duration: animDuration
			});
			
			// Not forgetting to remove the old content
			oldContent.remove();
			
		}
	});
	
}