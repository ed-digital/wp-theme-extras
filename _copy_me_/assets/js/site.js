SiteKit.xhrOptions.swapContent = function(container, oldContent, newContent, direction) {
	
	var animDuration = 200;
	
	var isNewsListing = (oldContent.find(".latest-stuff").size() && newContent.find(".latest-stuff").size());
	
	if(isNewsListing) {
		
		var fadeClasses = ".post-items, .more-posts";
		
		oldContent.find(fadeClasses).fadeOut({
			duration: animDuration,
			complete: function() {
				
				oldContent.remove();
				newContent.show();
				
				newContent.find(fadeClasses).hide().fadeIn({
					duration: animDuration
				});
				
			}
		})
	
	} else {
		
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
	
}