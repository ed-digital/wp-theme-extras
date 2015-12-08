var SiteKit = {};
(function($) {
	
	SiteKit.initWidgets = function(targetEl) {
		targetEl = $(targetEl || document.body);
		
		// Look for uninitialized widgets
		targetEl.find("[data-widget]").each(function() {
			
			// Grab the element and data
			var el = $(this);
			var data = el.data();
			
			// Only initialize once
			if(data.hasBeenInitialized) return;
			
			// Throw an error if that widget doesn't exist
			if(data.widget in $.fn === false) {
				if(data.widgetOptional === true) {
					return;
				} else {
					throw new Error("Could not initialize widget '"+data.widget+"', as no widget with this name has been declared.");
				}
			}
			
			// Mark as initialized
			el.data('hasBeenInitialized', true);
			
			var args = {};
			
			for(var k in data) {
				if(k[0] !== "_") {
					args[k] = data[k];
				}
			}
			
			// Spawn the widget
			el[data.widget](args);
			
		});
		
	};
	
	SiteKit.xhrOptions = {
		scrollAnimation: {
			duration: 400
		},
		loadImages: true,
		imageLoadTimeout: 3000,
		widgetTransitionDelay: 0,
		swapContent: function(container, originalContent, newContent, direction) {
			
			// Fade out old content
			originalContent.fadeOut({
				duration: 200,
				complete: function() {
					
					// Not forgetting to remove the old content
					originalContent.remove();
					
					// Fade in new content
					newContent.fadeIn({
						duration: 200
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
	
	SiteKit.goToURL = function(url, dontPush) {
		
		var originalURL = url;
		var requestID = ++XHRRequestCounter;
		
		if(url.indexOf('?') == -1) {
			url += "?xhr-page=true";
		} else {
			url += "&xhr-page=true";
		}
		
		var htmlBody = $("html,body").stop(true).animate({scrollTop: 0}, SiteKit.xhrOptions.scrollAnimation).one('scroll', function() {
			htmlBody.stop(true);
		});
		
		$(document.body).trigger("xhrLoadStart");
		
		$.get(url, function(response, textStatus) {
			if(requestID !== XHRRequestCounter) {
				// Looks like another request was made after this one, so ignore the response.
				return;
			}
			
			$(document.body).trigger("xhrTransitioningOut");
			
			// Alter the response to keep the body tag
			response = response.replace(/(<\/?)body/g, '$1bodyfake');
			
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
			
			$(document.body).trigger("xhrLoadMiddle");
			
			var finalize = function() {
				
				$(document.body).trigger("xhrLoadEnd");
				
				// Grab the page title
				var title = result.find("title").html();
				
				// Grab any resources
				var includes = result.find("script, link[rel=stylesheet]");
				
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
				
				// Destroy existing widgets
				SiteKit.transitionWidgetsOut(SiteKit.XHRPageContainer, oldPageState, SiteKit.pageState, true);
				
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
				
				// Set up links and widgets
				newContent.hide();
				setTimeout(function() {
					newContent.show();
					SiteKit.initWidgets(newContent);
					SiteKit.handleXHRLinks(newContent);
					newContent.hide();
				}, SiteKit.xhrOptions.widgetTransitionDelay);
				
				// Perform the swap!
				SiteKit.xhrOptions.swapContent(SiteKit.XHRPageContainer, oldContent, newContent, dontPush ? "back" : "forward");
				
				// Call _transitionIn function for the new widgets
				setTimeout(function() {
					SiteKit.transitionWidgetsIn(newContent, SiteKit.pageState, oldPageState);
				}, SiteKit.xhrOptions.widgetTransitionDelay);
				
				// Apply to history
				if(!dontPush) {
					history.pushState({}, title, originalURL);
				}
			
			};
			
			if(SiteKit.xhrOptions.loadImages) {
				SiteKit.preloadContent(newContent, SiteKit.xhrOptions.imageLoadTimeout, finalize);
			} else {
				finalize();
 			}
			
		});
		
	};
	
	SiteKit.transitionWidgetsIn = function(targetEl, newState, oldState) {
		
		targetEl.find("[data-widget]").each(function() {
			
			var el = $(this);
			var widgetName = this.getAttribute('data-widget');
			
			var widget = el[widgetName]('instance');
			
			if(widget._transitionIn) {
				widget._transitionIn(newState, oldState);
			}
			
		});
		
	};
	
	SiteKit.transitionWidgetsOut = function(targetEl, newState, oldState, destroy) {
		
		targetEl.find("[data-widget]").each(function() {
			
			var el = $(this);
			var widgetName = this.getAttribute('data-widget');
			
			var widget = el[widgetName]('instance');
			
			if(widget._transitionOut) {
				setTimeout(function() {
					widget._transitionOut(newState, oldState);
				}, 1);
			}
			
			if(destroy) {
				widget.destroy();
			}
		});
		
	};
	
	SiteKit.initXHRPageSystem = function() {
		
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
			if(e.state) {
				SiteKit.goToURL(window.location.href, true);
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
			
			linkEl.click(function(e) {
				e.preventDefault();
				SiteKit.goToURL(this.href);
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
		
		if(srcs.length === 0) {
			callback();
			return;
		}
		
		var images = [];
		
		var callbackCalled = false;
		var triggerCallback = function() {
			if(!callbackCalled) {
				callbackCalled = true;
				if(callback) callback();
			}
		};
		
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
	
	// Boot up
	$(function() {
		
		var body = $(document.body);
		
		SiteKit.pageState = body.children("pagestate").data('state');
		
		SiteKit.initWidgets();
		SiteKit.initXHRPageSystem();
		
		SiteKit.transitionWidgetsIn(body, SiteKit.pageState, null);
	});
	
})(jQuery);
if(jQuery) $ = jQuery;
