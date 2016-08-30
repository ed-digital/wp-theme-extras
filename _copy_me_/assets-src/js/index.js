import _babel from 'babel-polyfill';
import Site from 'sitekit';
import * as widgets from "glob:./widgets/*.js";

// Create new site
const site = new Site();

// Run widget functions
for(let item of Object.values(widgets)) {
  if(typeof item == 'function') {
    item(site, site.$);
  } else {
    console.error("One widget file did not export a function.");
  }
}

// Install globals for dev purposes
window.Site = site;
window.jQuery = window.$ = require('jquery');