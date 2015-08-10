$.widget("sage.postArchive", {
	
	_create: function() {
		
		var self = this;
		
		this.container = this.element.find(".post-items");
		
		this.element.find(".more-posts .link").click(function() {
			self.loadMore();
		});
		
	},
	
	loadMore: function() {
		
		var self = this;
		
		var skip = this.element.find(".post-items .item").size();
		
		var url = window.location.href + (window.location.href.indexOf("?") == -1 ? '?skip=' : '&skip=') + skip;
		
		$.get(url, function(content) {
			
			var content = $(content);
			var items = content.find(".post-items").children();
			
			if(content.find(".more-posts").size() == 0) {
				self.element.find(".more-posts").hide();
			}
			
			items.appendTo(self.container).each(function(index) {
				$(this).hide().delay(index * 100).fadeIn({
					duration: 300
				});
			});
			
		});
		
	}
	
});