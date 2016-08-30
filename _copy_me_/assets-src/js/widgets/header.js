import fs from 'fs';

module.exports = (Site, $) => {
  
  Site.widget('header', {
    _create() {
      
      setInterval(() => {
        // this.element.css('padding-left', Math.random() * 100);
      }, 10);
      
    }
  });
  
};