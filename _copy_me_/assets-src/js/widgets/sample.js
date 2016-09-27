import fs from 'fs';

module.exports = (Site, $) => {
  
  /*
  
  Site.widget('header', {
    _create() {
      
      this.element.find(".mobile-menu-toggle").click(() => {
        $(document.body).toggleClass("nav-active");
      });
      
    }
  });
  
  // Example markup: <div class="gallery" data-widget="gallery" data-items="[\"cool.jpg\",\"something.jpg\"]"
  Site.widget('gallery', {
    options: {
      items: []     // Accepts a list of images
    },
    _create() {
      
      // Preload images :)
      Site.preloadImages(this.options.items);
      
      this.currentIndex = 0;
      this.inner = $("<div class='inner'></div>").appendTo(this.element);
      this.frame = $("<div class='frame'></div>").appendTo(this.inner);
      this.controls = $("<div class='controls'></div>").appendTo(this.inner);
      
      this.left = $("<div class='item left'></div>").appendTo(this.controls);
      this.right = $("<div class='item right'></div>").appendTo(this.controls);
      
      this.left.click(() => this.go(-1));
      this.right.click(() => this.go(1));
      
      this.go(0);
      
    },
    go(dir) {
      if(this.disabled) return;
      this.disabled = true;
      
      this.currentIndex += dir;
      
      if(this.currentIndex >= this.options.length) {
        this.currentIndex = 0;
      } else if(this.currentIndex < 0) {
        this.currentIndex = this.options.items.length - 1;
      }
      
      this.frame.fadeOut({
        duration: 500,
        complete: () => {
          
          this.frame
            .css('background-image', 'url("'+this.options.items[this.currentIndex].src+'")')
            .fadeIn({
              duration: 500,
              complete: () => {
                this.disabled = false;
              }
            });  
          
        }
      });
    }
    
  });
  
  */
  
};