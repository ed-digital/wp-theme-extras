$.widget('ui.postSlider', {
	options: {
		
	},
	_create: function() {
		
		var self = this;
		
		var sliderEl = this.element.find(".slider");
		
		var forwardArrow = this.element.find(".arrow.forward").click(function() {
			self.element.edSlider('go', 1);
		});
		var backArrow = this.element.find(".arrow.back").click(function() {
			self.element.edSlider('go', -1);
		});
		
		this.element.edSlider({
 			autoplay: false,
			slides: this.element.find(".item"),
			getMaxSlide: function() {
				return this.slides.length - 2;
			},
			transitionSlide: function(slider) {
				
				var targetLeft = -slider.currentSlide.position().left;
				if(slider.instant) {
					sliderEl.stop(true).css({
						left: targetLeft
					});
				} else {
					sliderEl.stop(true).animate({
						left: targetLeft
					}, {
						duration: 400,
						easing: 'easeInOutQuad'
					});
				}
				
			},
			afterSlideChanged: function(slider) {
				
			}
		});
		
	}
});