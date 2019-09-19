import 'babel-polyfill'
import SiteInstance from 'sitekit'

// Create new site
const Site = new SiteInstance()
window.Site = Site

// Save jQuery as a global. You may need to remove this for some plugins
window.jQuery = window.$ = Site.$

// Include widgets
const widgets = require.context('./widgets', false, /\.js$/)
const parts = require.context('../../components', true, /\.js$/)

widgets.keys().forEach(id => {
  const module = widgets(id)
  const call = module.default ? module.default : module
  if (typeof call === 'function') call(Site, Site.$)
})

parts.keys().forEach(id => {
  const module = parts(id)
  const call = module.default ? module.default : module
  if (typeof call === 'function') call(Site, Site.$)
})
