$.widget("sage.slider", {
	_create: function() {
		this.element.edSlider(this.options);
	},
	_destroy: function() {
		this.element.edSlider('destroy');
	}
});
$.widget("ed.edSlider", {
	options: {
		interval: 3000,
		autoplay: true,
		slides: null,
		resized: function() {
			
		},
		getMaxSlide: function() {
			return this.slides.length - 1;
		},
		transitionSlide: function(slider) {
			
			slider.slides.not(slider.currentSlide).not(slider.lastSlide).hide();
			
			slider.lastSlide.hide();
			slider.currentSlide.show();
			
		},
		beforeSlideChange: function(slider) { },
		afterSlideChange: function(slider) { }
	},
	_create: function() {
		
		var self = this;
		
		self.sliderID = Math.floor(Math.random() * 100000);
		
		self.slides = this.options.slides || this.element.find(".slide");
		self.currentIndex = 0;
		
		// Event handlers
		self.element.mouseenter(function() {
			
		});
		$(window).bind('resize.'+self.sliderID, function() {
			self.resize();
		});
		
		// Trigger resize now
		self.resize();
		
		// Preload
		if(SiteKit && SiteKit.preloadContent) {
			self.element.addClass("loading");
			SiteKit.preloadContent(self.element, 5000, function() {
				self.element.removeClass("loading");
				self.start();
			});
		} else {
			self.start();
		}
		
		this.go(0, true);
		
	},
	_destroy: function() {
		$(window).unbind('resize.'+this.sliderID);
	},
	resize: function() {
		
		
	},
	// Play if autoplay enabled
	start: function() {
		if(this.options.autoplay) {
			this.play();
		}
	},
	// Start playing
	play: function() {
		var self = this;
		clearInterval(this.interval);
		this.interval = setInterval(function() {
			self.go(+1);
		}, self.options.interval);
	},
	// Halts the timer
	pause: function() {
		clearInterval(this.interval);
	},
	// Calls play, but only if paused.
	resume: function() {
		if(this.interval) {
			this.play();
		}
	},
	// Clears the timer but also sets the interval to 'null' to prevent resume() from calling play()
	stop: function() {
		clearInterval(this.interval);
		this.interval = null;
	},
	// Go in a given direction
	go: function(direction, instant) {
		var index = this.currentIndex + direction;
		var maxSlide = this.options.getMaxSlide.call(this, this.slides);
		if(index > maxSlide) {
			index = 0;
		} else if(index < 0) {
			index = maxSlide - 1;
		}
		this.goToSlide(index, direction, instant);
	},
	goToSlide: function(index, direction, instant) {
		
		this.lastIndex = this.currentIndex;
		this.currentIndex = index;
		
		this.lastSlide = this.slides.eq(this.lastIndex);
		this.currentSlide = this.slides.eq(this.currentIndex);
		
		this.direction = direction;
		
		this.instant = instant;
		
		this.options.beforeSlideChange(this);
		this.options.transitionSlide(this);
		this.options.afterSlideChange(this);
		
	}
});