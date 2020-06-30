interface Window {
  wp: any
  _ED_TEMPLATES: {
    [id: string]: {
      name: string
      template: {
        [k: string]: any
      }
    }
  }
}

const { parse, synchronizeBlocksWithTemplate, serialize } = window.wp.blocks
const { select, subscribe, dispatch } = window.wp.data

const DEFAULT = "wp_default_template"

console.log("installed")

class PageTemplateSwitcher {
  template: string = DEFAULT
  previousTemplate: string = DEFAULT

  init() {
    subscribe(() => {
      const newTemplate = select("core/editor").getEditedPostAttribute("template") || DEFAULT

      if (newTemplate !== this.template) {
        this.previousTemplate = this.template
        this.template = newTemplate
        this.changeTemplate()
      }
    })
  }

  changeTemplate() {
    /* Registered templates are stored in window._ED_TEMPLATES */
    const TEMPLATES = window._ED_TEMPLATES
    const { resetBlocks, updatePost } = dispatch("core/editor")
    const editor = select("core/editor")
    const postType = editor.getEditedPostAttribute("type")
    const id = editor.getEditedPostAttribute("id")
    const blocks = editor.getBlocks()

    console.log(TEMPLATES)

    if (postType && id) {
      /* ID for PREVIOUS content.  */
      const previousId = `${postType}:${id}:${this.previousTemplate}`
      const globalId = `${postType}:${id}:${this.template}`

      /* Save this template to session storage */
      if (globalId && blocks) {
        const value = serialize(blocks)
        sessionStorage.setItem(previousId, value)
      }

      /*
      If the user is switching back and forth between templates we want to save the data for each template
      The other version of the template is only saved for that session
      */
      const retrievedContent = sessionStorage.getItem(globalId)

      /* Retrieve template layout from the server */
      if (this.template !== DEFAULT) {
        if (this.template in TEMPLATES) {
          const theTemplate = TEMPLATES[this.template].template
          if (theTemplate) {
            const theBlocks = retrievedContent ? parse(retrievedContent) : blocks
            const shapedBlocks = synchronizeBlocksWithTemplate(theBlocks, theTemplate)
            resetBlocks(shapedBlocks)
            updatePost({ content: serialize(shapedBlocks) })
            return
          }
        }
      } else {
        /*
        When returning to the default template just use the content that was already there
          (Which in most cases will be nothing, unless you are manually entering post content from wordpress)
        */
        resetBlocks(retrievedContent ? parse(retrievedContent) : blocks)
      }
    }
  }
}

let templateSwitcher = new PageTemplateSwitcher()
templateSwitcher.init()
