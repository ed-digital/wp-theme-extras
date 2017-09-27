import _babel from 'babel-polyfill';
import SiteInstance from 'sitekit';
import * as widgets from "glob:./widgets/*.js";

// Create new site
const Site = new SiteInstance();

// Install globals for dev purposes
window.Site = Site;
window.jQuery = window.$ = Site.$;

// Run widget functions
for(let item of Object.values(widgets)) {
  if(typeof item == 'function') {
    item(Site, Site.$);
  } else {
    if(window.console && window.console.error) {
      console.error("One widget file did not export a function.");
    }
  }
}