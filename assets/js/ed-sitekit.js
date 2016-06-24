var SiteComponents = {};
var SiteKit = {
	components: SiteComponents
};
(function($) {
	
	SiteKit.keyCodes = {
		BACKSPACE: 8,
		COMMA: 188,
		DELETE: 46,
		DOWN: 40,
		END: 35,
		ENTER: 13,
		ESCAPE: 27,
		HOME: 36,
		LEFT: 37,
		PAGE_DOWN: 34,
		PAGE_UP: 33,
		PERIOD: 190,
		RIGHT: 39,
		SPACE: 32,
		TAB: 9,
		UP: 38
	};
	
	SiteKit.baseWidget = {
		debounce: function(callback, time, name) {
			
			var self = this;
			
			name = name || '_';
			
			self._scheduledTimers = self._scheduledTimers || {};
			
			clearTimeout(this._scheduledTimers[name]);
			
			this._scheduledTimers[name] = setTimeout(function() {
				callback.call(self);
			}, time);
			
		},
		afterInit: function(callback) {
			var self = this;
			$(document).bind('afterWidgetsInit.'+this.uuid, function() {
				callback.call(self);
				$(document).unbind('afterWidgetsInit.'+self.uuid);
			});
		}
	};
	
	SiteKit.widget = function(name, def) {
		if(name.indexOf('.') === -1) {
			name = 'ui.'+name;
		}
		$.widget(name, $.extend({}, SiteKit.baseWidget, def));
	};
	
	SiteKit.findWidgets = function(name, el) {
		var result = [];
		var widgets = $('[data-widget]', el || document.body).each(function() {
			var widgetNames = $.data(this, 'widgetNames');
			if(widgetNames && widgetNames.indexOf(name) != -1) {
				var instance = $(this)[name]('instance');
				result.push(instance);
			}
		});
		return result;
	};
	
	SiteKit.findWidget = function(name, el) {
		var widgets = SiteKit.findWidgets(name, el);
		if(widgets && widgets.length) {
			return widgets[0];
		} else {
			return null;
		}
	};
	
	SiteKit.getAllWidgets = function(el) {
		var result = [];
		var widgets = $('[data-widget]', el || document.body).each(function() {
			var widgetNames = $.data(this, 'widgetNames');
			for(var k in widgetNames) {
				var instance = $(this)[widgetNames[k]]('instance');
				if(instance) {
					result.push(instance);
				}
			}
		});
		return result;
	};
	
	SiteKit.triggerAllWidgets = function(methodName) {
		
		var args = Array.prototype.slice.call(arguments, 1);
		
		var widgets = SiteKit.getAllWidgets();
		for(var k in widgets) {
			var widget = widgets[k];
			if(widget && methodName in widget) {
				widget[methodName].apply(widget, args);
			}
		}
		
	};
	
	SiteKit.getWidgetDefs = function(el) {
		
		el = $(el);
		
		var widgets = (el.attr('data-widget') || el.attr('data-widgets')).split(/\,\s*/g);
		var isInitialized = el.data('hasBeenInitialized');
		
		for(var k in widgets) {
			
			var widgetInfo = widgets[k].split('#');
			
			widgets[k] = {
				name: widgetInfo[0],
				identifier: widgetInfo[1],
				instance: isInitialized ? el[widgetInfo[0]]('instance') : null
			};
			
		}
		
		return widgets;
		
	};
	
	SiteKit.initWidgets = function(targetEl) {
		targetEl = $(targetEl || document.body);
		
		// Look for uninitialized widgets
		targetEl.find("[data-widget]").each(function() {
			
			// Grab the element and data
			var el = $(this);
			var data = el.data();
			
			// Only initialize once
			if(data.hasBeenInitialized) return;
			
			// Prepare options
			var options = {};
			for(var k in data) {
				if(k[0] !== "_") {
					options[k] = data[k];
				}
			}
			
			var widgets = SiteKit.getWidgetDefs(el);
			var widgetNames = [];
			for(var i = 0; i < widgets.length; i++) {
				
				var widget = widgets[i];
				
				widgetNames.push(widget.name);
				
				// Throw an error if that widget doesn't exist
				if(widget.name in $.fn === false) {
					if(data.widgetOptional === true) {
						return;
					} else {
						if(console && console.error) {
							console.error("Could not initialize widget '"+widget.name+"', as no widget with this name has been declared.");
						}
					}
				}
				
				// Spawn the widget, and grab it's instance
				el[widget.name](options);
				var instance = el[widget.name]('instance');
				
				// Save it to the components object
				if(widget.identifier) {
					SiteKit.components[widget.identifier] = instance;
				}
			
			}
			
			// Mark as initialized
			el.data('hasBeenInitialized', true);
			$.data(el[0], 'widgetNames', widgetNames);
			
		});
		
		$(document).trigger('afterWidgetsInit');
		
	};
	
	SiteKit.setGlobalState = function(state, reset) {
		
		for(var k in SiteKit.components) {
			var component = SiteKit.components[k];
			if(component && component.setState) {
				if(k in state || reset !== false) {
					component.setState(state[k] || {});
				}
			}
		}
		
	};
	
	SiteKit.xhrOptions = {
		scrollAnimation: {
			duration: 400
		},
        xhrEnabled: true,
		loadImages: true,
		imageLoadTimeout: 3000,
		widgetTransitionDelay: 0,
		cachePages: false,
		swapContent: function(container, originalContent, newContent, direction) {
			
			var duration = SiteKit.xhrOptions.widgetTransitionDelay || 500;
			
			// Fade out old content
			originalContent.fadeOut({
				duration: duration/2,
				complete: function() {
					
					// Not forgetting to remove the old content
					originalContent.remove();
					
					// Fade in new content
					newContent.css({
						display: inline,
						opacity: 0
					}).animate({
						opacity: 1
					}, {
						duration: duration/2
					});
					
				}
			});
			
		},
		filterBodyClasses: function(oldClasses, newClasses) {
			return newClasses;
		}
	};
	
	SiteKit.setupXHR = function(options) {
		$.extend(SiteKit.xhrOptions, options);
	};
	
	var XHRRequestCounter = 0;
	
	SiteKit.getContent = function(url, callback, isPreload) {
		
		url = url.replace(/\#.+/, '');
		
		if(url.indexOf('?') == -1) {
			url += "?xhr-page=true";
		} else {
			url += "&xhr-page=true";
		}
		
		// Mark as preloaded (even if it's not). It won't appear in pageCache until it's been completely loaded. This is just to prevent the page from being preloaded more than once.
		if(isPreload && SiteKit.preloadedPages[url]) {
			callback();
			return;
		}
		SiteKit.preloadedPages[url] = true;
		
		if(SiteKit.xhrOptions.cachePages && SiteKit.pageCache[url]) {
			callback(SiteKit.pageCache[url]);
		} else {
			$.ajax({
				'url': url,
				'async': true,
				'global': !isPreload,
				'success': function(response, textStatus) {
					callback(response, textStatus, null);
					if(response && SiteKit.xhrOptions.cachePages) {
						SiteKit.pageCache[url] = response;
					}
				},
				'error': function(jqXHR, textStatus, error) {
					callback(jqXHR.responseText, textStatus, error);
				}
			});
		}
		
	};
	
	SiteKit.pageCache = {};
	SiteKit.preloadedPages = {};
	
	SiteKit.pagePreloadQueue = [];
	SiteKit.isPreloadingPages = false;
	SiteKit.preloadPages = function() {
		if(SiteKit.isPreloadingPages) return;
		SiteKit.isPreloadingPages = true;
		
		var loadNext = function() {
			
			// Filter out pre-preloaded urls
			SiteKit.pagePreloadQueue = SiteKit.pagePreloadQueue.filter(function(url) {
				return SiteKit.preloadedPages[url] ? false : true;
			});
			
			if(SiteKit.pagePreloadQueue.length === 0) {
				SiteKit.isPreloadingPages = false;
			} else {
				var url = SiteKit.pagePreloadQueue.shift();
				SiteKit.getContent(url, function() {
					setTimeout(loadNext);
				}, true);
			}
		};
		
		loadNext();
		
	};
	
	SiteKit.goToURL = function(url, dontPush) {
		
		var originalURL = url;
		var requestID = ++XHRRequestCounter;
		
		SiteKit.lastURL = url;		
		
		// See if any widgets want to intercept this request instead
		var allWidgets = SiteKit.getAllWidgets();
		var urlPath = url.match(/:\/\/[^\/]+(.*)/);
		for(var k in allWidgets) {
			var widget = allWidgets[k];
			if(widget && widget.xhrPageWillLoad) {
				var result = widget.xhrPageWillLoad(urlPath, url);
				if(result === false) {
					history.pushState({}, null, originalURL);
					return;
				}
			}
		}
		
		var htmlBody = $("html,body").stop(true).animate({scrollTop: 0}, SiteKit.xhrOptions.scrollAnimation).one('scroll', function() {
			htmlBody.stop(true);
		});
		
		$(document).trigger("xhrLoadStart");
		
		SiteKit.getContent(originalURL, function(response, textStatus) {
			
			if(requestID !== XHRRequestCounter) {
				// Looks like another request was made after this one, so ignore the response.
				return;
			}
			
			$(document).trigger("xhrTransitioningOut");
			
			// Alter the response to keep the body tag
			response = response.replace(/(<\/?)body/g, '$1bodyfake');
			response = response.replace(/(<\/?)head/g, '$1headfake');
			
			// Convert the text response to DOM structure
			var result = $("<div>"+response+"</div>");
			
			// Pull out the contents
			var foundPageContainer = result.find("[data-page-container]:first");
			
			if(foundPageContainer.size() === 0) {
				// Could not find a page container element :/ just link to the page
				window.location.href = originalURL;
				console.error("Could not find an element with a `data-page-container` attribute within the fetched XHR page response. Sending user directly to the page.");
				return;
			}
			
			// Grab content
			var newContent = $("<div class='xhr-page-contents'></div>").append(foundPageContainer.children());
			
			$(document).trigger("xhrLoadMiddle");
			
			var finalize = function() {
				$(document).trigger("xhrLoadEnd");
				
				// Grab the page title
				var title = result.find("title").html();
				
				// Grab any resources
				var includes = result.find("headfake").find("script, link[rel=stylesheet]");
				
				// Grab the body class
				var bodyClass = result.find("bodyfake").attr('class');
				bodyClass = SiteKit.xhrOptions.filterBodyClasses(document.body.className, bodyClass);
				
				var oldPageState = SiteKit.pageState;
				SiteKit.pageState = result.find("pagestate").data('state');
				
				// Set page title
				$("head title").html(title);
				document.body.className = bodyClass + " xhr-transitioning-out";
				
				var existingScripts = $(document.head).find("script");
				var existingStylesheets = $(document.head).find("link[rel=stylesheet]");
				
				// Swap menus out
				result.find("ul.menu").each(function() {
					
					var id = this.getAttribute('id');
					var el = $('#'+id).html(this.innerHTML);
					SiteKit.handleXHRLinks(el);
					
				});
				
				// Swap WP 'Edit Post' link
				var editButton = result.find("#wp-admin-bar-edit");
				if(editButton.size()) {
					$("#wp-admin-bar-edit").html(editButton.html());
				}
				
				// Apply any missing scripts
				includes.each(function() {
						
					if($(this).parents("[data-page-container]").size()) return;
					
					if(this.tagName == "SCRIPT") {
						
						var scriptSrc = this.src.replace(/\?.*$/, '');
						var includeScript = true;
						existingScripts.each(function() {
							var thisSrc = this.src.replace(/\?.*$/, '');
							if(scriptSrc == thisSrc) {
								includeScript = false;
							}
						});
						
						if(includeScript) {
							$(this).appendTo(document.head);
						}
						
					} else if(this.tagName == "LINK") {
						
						var linkHref = this.href.replace(/\?.*$/, '');
						var includeStyles = true;
						existingStylesheets.each(function() {
							var thisHref = this.href.replace(/\?.*$/, '');
							if(linkHref == thisHref) {
								includeStyles = false;
							}
						});
						
						if(includeStyles) {
							$(this).appendTo(document.head);
						}
						
					}
					
				});
				
				// Grab old content, by wrapping it in a span
				SiteKit.XHRPageContainer.wrapInner("<div class='xhr-page-contents'></div>");
				var oldContent = SiteKit.XHRPageContainer.children().first();
				
				// Add new content to the page
				try {
					SiteKit.XHRPageContainer.append(newContent);
				} catch(e) {
					
				}
				
				newContent.hide();
				
				// Apply to history
				if(!dontPush) {
					history.pushState({}, title, originalURL);
				}
				
				// Destroy existing widgets
				var steps = [
					function(next) {
						SiteKit.transitionWidgetsOut(SiteKit.XHRPageContainer, oldPageState, SiteKit.pageState, true, next);
					},
					function(next) {
						// Set up links and widgets
						newContent.show();
						SiteKit.forceResizeWindow();
						SiteKit.initWidgets(newContent);
						SiteKit.handleXHRLinks(newContent);
						newContent.hide();
						
						// Perform the swap!
						var delay = SiteKit.xhrOptions.widgetTransitionDelay;
						delay = SiteKit.xhrOptions.swapContent(SiteKit.XHRPageContainer, oldContent, newContent, dontPush ? "back" : "forward") || delay;
						setTimeout(next, delay);
					},
					function(next) {
						SiteKit.transitionWidgetsIn(newContent, SiteKit.pageState, oldPageState, next);
					}
				];
				
				var stepIndex = 0;
				var next = function() {
					if(stepIndex < steps.length) {
						steps[stepIndex++](next);
					} else {
						$(document).trigger("xhrPageChanged");
					}
				}
				
				next();
			
			};
			
			if(SiteKit.xhrOptions.loadImages) {
				SiteKit.preloadContent(newContent, SiteKit.xhrOptions.imageLoadTimeout, finalize);
			} else {
				finalize();
 			}
			
		});
		
	};
	
	SiteKit.transitionWidgetsIn = function(targetEl, newState, oldState, callback) {
		
		var foundTransition = false;
		var finalDelay = 0;
		
		targetEl.find("[data-widget]").each(function() {
			
			var el = $(this);
			var widgets = SiteKit.getWidgetDefs(el);
			
			for(var k in widgets) {
				if(widgets[k].instance && widgets[k].instance._transitionIn) {
					var delay = widgets[k].instance._transitionIn(newState, oldState, SiteKit.xhrOptions.widgetTransitionDelay);
					foundTransition = true;
					finalDelay = Math.max(delay, finalDelay);
				}
			}
			
		});
		
		if(foundTransition && finalDelay) {
			setTimeout(callback, finalDelay);
		} else if(foundTransition && !finalDelay) {
			setTimeout(callback, SiteKit.xhrOptions.widgetTransitionDelay);
		} else {
			callback();
		}
		
	};
	
	SiteKit.transitionWidgetsOut = function(targetEl, newState, oldState, destroy, callback) {
		
		var foundTransition = false;
		var finalDelay = 0;
		
		targetEl.find("[data-widget]").each(function() {
			
			var el = $(this);
			var widgets = SiteKit.getWidgetDefs(el);
			
			for(var k in widgets) {
				var widget = widgets[k].instance;
				if(widget && widget._transitionOut) {
					foundTransition = true;
					var delay = widget._transitionOut(newState, oldState, SiteKit.xhrOptions.widgetTransitionDelay);
					finalDelay = Math.max(delay, finalDelay);
					if(destroy) {
						widget.destroy();
					}
				}
			}
		});
		
		if(foundTransition && finalDelay) {
			setTimeout(callback, finalDelay);
		} else if(foundTransition && !finalDelay) {
			setTimeout(callback, SiteKit.xhrOptions.widgetTransitionDelay);
		} else {
			callback();
		}
		
	};
	
	SiteKit.initXHRPageSystem = function() {

        if(!SiteKit.xhrOptions.xhrEnabled) return;
		
		// Grab the page container, if one exists
		SiteKit.XHRPageContainer = $("[data-page-container]:first");
		if(SiteKit.XHRPageContainer.size() === 0) {
			SiteKit.XHRPageContainer = null;
			return;
		}
		
		// Add event listeners to jQuery which will add/remove the 'xhr-loading' class
		$(document).ajaxStart(function() {
			$(document.body).addClass("xhr-loading");
			$(document).trigger("xhrLoadingStart");
		}).ajaxStop(function() {
			$(document.body).removeClass("xhr-loading");
			$(document).trigger("xhrLoadingStop");
		});
		
		// Add event listeners to links where appropriate
		SiteKit.handleXHRLinks();
		
		// Handle browser back button
		window.addEventListener("popstate", function(e) {
			var popped = ('state' in window.history && window.history.state !== null);
			if(popped) {
				if(e.state) {
					SiteKit.goToURL(window.location.href, true);
				} else {
					window.location.reload();
				}
			}
		});
		
	};
	
	SiteKit.handleXHRLinks = function(targetEl) {
		
		targetEl = $(targetEl || document.body);
		
		//var baseURL = document.baseURI.match(/^[a-z]+[\:\/]+[^\/]+/i)[0];
        var baseURL = window.location.origin;
		
		targetEl.find("a").each(function() {
			var linkEl = $(this);
			if(linkEl.data('prevent-xhr') || linkEl.data('xhr-event-added')) return;
			if(linkEl.attr('target')) return;
			
			if(linkEl.parents("#wpadminbar").size() || linkEl.parents("[data-prevent-xhr]").size()) return;
			
			// Ensure the URL is usable
			var url = this.href;
			if(url.indexOf(baseURL) !== 0) {
				// Link is not on this site
				return;
			}
			if(url.match(/\.[a-z]$/i) || url.match(/^(mailto|tel)\:/i)) {
				// Link is a file
				return;
			}
			if(url.match(/\#/)) {
				// link contains a hashbang
				return;
			}
			
			SiteKit.pagePreloadQueue.push(this.href);
			SiteKit.preloadPages();
			
			linkEl.click(function(e) {
				if(!e.metaKey && !e.ctrlKey) {
					e.preventDefault();
					$(document).trigger('xhrLinkClick', [linkEl]);
					SiteKit.goToURL(this.href);
				}
			});
			
		});
		
	};
	
	SiteKit._xhrErrorCodes = {
		"timeout": "Timed out while making API request",
		"abort": "XHR request was aborted",
		"error": "XHR request encountered an error",
		"parsererror": "Unable to parse API request"
	};
	
	SiteKit.callAPI = function(method, args, callback) {
		
		if(args instanceof Function) {
			callback = args;
			args = null;
		}
		
		$.ajax({
			method: 'post',
			url: "/json-api/"+method,
			data: JSON.stringify(args),
			dataType: "json",
			success: function(response) {
				callback(response.error, response.result);
			},
			error: function(jqXHR, textStatus, errorThrown) {
				var message = "";
				if(textStatus && SiteKit._xhrErrorCodes[textStatus]) {
					message = SiteKit._xhrErrorCodes[textStatus];
				} else {
					message = "Server error occurred while making API request";
				}
				if(errorThrown && message) {
					message += ": "+errorThrown;
				}
				callback({
					code: textStatus || errorThrown,
					message: message,
					info: null
				}, null);
			}
		});
		
	};
	
	var preloadedImages = [];
	
	SiteKit.preloadImages = function(srcs, timeout, callback) {
		
		var images = [];
		
		var callbackCalled = false;
		var triggerCallback = function() {
			if(!callbackCalled) {
				callbackCalled = true;
				if(callback) callback();
			}
		};
		
		if(srcs.length === 0) {
			triggerCallback();
			return;
		}
		
		var loaded = function(img) {
			images.push(img);
			preloadedImages.push(img);
			if(images.length == srcs.length) {
				triggerCallback();
			}
		};
		
		if(timeout !== false) {
			setTimeout(function() {
				triggerCallback();
			}, timeout);
		}
		
		$(srcs).each(function(k, src) {
			
			var img = $("<img>");
			
			var hasLoaded = false;
			
			img.on("load", function() {
				if(!hasLoaded) {
					loaded(img);
				}
			});
			
			img[0].src = src;
			
			if(img[0].width || img[0].naturalWidth) {
				if(!hasLoaded) {
					hasLoaded = true;
					loaded(img);
				}
			}
			
		});
	};
	
	/*
		Preloads images from the specified elements, with an optional timeout.
		Callback will be triggered when their all elements have loaded, or when the timeout (in milliseconds) is reached.
		Set timeout to false for no timeout.
		
		eg.
			SiteKit.preloadContent(els, 5000, callback);
			SiteKit.preloadContent(els, false, callback);
	*/
	SiteKit.preloadContent = function(els, timeout, callback) {
		
		var images = [];
		var callbackCalled = false;
		
		$(els).each(function() {
			
			var self = $(this);
			
			// Get images from 'style' attribute of div elements only
			self.find("div[style]").each(function() {
				if(this.style.backgroundImage && typeof this.style.backgroundImage == 'string') {
					var match = this.style.backgroundImage.match(/url\((.+)\)/);
					if(match) {
						var src = match[1].replace(/(^[\'\"]|[\'\"]$)/g, '');
						images.push(src);
					}
				}
			});
			
			// Get 'src' attributes from images
			self.find("img").each(function() {
				images.push(this.src);
			});
			
		});
		
		SiteKit.preloadImages(images, timeout, callback);
		
	};
	
	SiteKit.getURLPath = function(input) {
		var match = input.match(/:\/\/[^\/]+([^#?]*)/);
		if(match) {
			return match[1].replace(/\/$/, '');
		} else {
			return "";
		}
	};
	
	SiteKit.resizeToFit = function(width, height, viewportWidth, viewportHeight, cover) {
		
		var result = {};
		
		if((cover && width/height > viewportWidth/viewportHeight) || (!cover && width/height < viewportWidth/viewportHeight)) {
			result.width = viewportHeight * width/height;
			result.height = viewportHeight;
		} else {
			result.width = viewportWidth;
			result.height = viewportWidth * height/width;
		}
		
		result.top = viewportHeight/2 - result.height/2;
		result.left = viewportWidth/2 - result.width/2;
		
		return result;
		
	};
	
	SiteKit.forceResizeWindow = function() {
		window.resizeTo(window.outerWidth, window.outerHeight);
	};
	
	// Handle keypresses
	$(window).on('keydown', function(e) {
		
		// Determine if an array key has been pressed
		var arrowDirection;
		if(e.keyCode === SiteKit.keyCodes.LEFT) {
			arrowDirection = 'left';
		} else if(e.keyCode === SiteKit.keyCodes.RIGHT) {
			arrowDirection = 'right';
		} else if(e.keyCode === SiteKit.keyCodes.UP) {
			arrowDirection = 'up';
		} else if(e.keyCode === SiteKit.keyCodes.DOWN) {
			arrowDirection = 'down';
		}
		
		// If so, trigger arrowDown on any widgets with that function
		if(arrowDirection) {
			var widgets = SiteKit.getAllWidgets();
			for(var k in widgets) {
				var widget = widgets[k];
				if(widget.arrowDown) {
					widget.arrowDown(arrowDirection, e);
				}
			}
		}
		
		// Also look for ESC
		if(e.keyCode === SiteKit.keyCodes.ESCAPE) {
			SiteKit.triggerAllWidgets('escapeDown', e);
		}
		
	});
	
	// Boot up
	$(function() {
		
		var body = $(document.body);
		
		SiteKit.pageState = body.children("pagestate").data('state');
		
		SiteKit.initWidgets();
		SiteKit.initXHRPageSystem();
		
		SiteKit.transitionWidgetsIn(body, SiteKit.pageState, null, function() {});
	});
	
})(jQuery);
if(jQuery) $ = jQuery;