import url from 'url';

module.exports = (Site, $) => {
  
  Site.widget('myCoolWidget', {
    _create() {
      
      let urlParts = url.parse(document.location.href);
      
      console.log("Parsed URL is",urlParts);
      
    }
  });
  
};