var _a = window.wp.blocks, parse = _a.parse, synchronizeBlocksWithTemplate = _a.synchronizeBlocksWithTemplate, serialize = _a.serialize;
var _b = window.wp.data, select = _b.select, subscribe = _b.subscribe, dispatch = _b.dispatch;
var DEFAULT = "wp_default_template";
console.log("installed");
var PageTemplateSwitcher = /** @class */ (function () {
    function PageTemplateSwitcher() {
        this.template = DEFAULT;
        this.previousTemplate = DEFAULT;
    }
    PageTemplateSwitcher.prototype.init = function () {
        var _this = this;
        subscribe(function () {
            var newTemplate = select("core/editor").getEditedPostAttribute("template") || DEFAULT;
            if (newTemplate !== _this.template) {
                _this.previousTemplate = _this.template;
                _this.template = newTemplate;
                _this.changeTemplate();
            }
        });
    };
    PageTemplateSwitcher.prototype.changeTemplate = function () {
        /* Registered templates are stored in window._ED_TEMPLATES */
        var TEMPLATES = window._ED_TEMPLATES;
        var _a = dispatch("core/editor"), resetBlocks = _a.resetBlocks, updatePost = _a.updatePost;
        var editor = select("core/editor");
        var postType = editor.getEditedPostAttribute("type");
        var id = editor.getEditedPostAttribute("id");
        var blocks = editor.getBlocks();
        console.log(TEMPLATES);
        if (postType && id) {
            /* ID for PREVIOUS content.  */
            var previousId = postType + ":" + id + ":" + this.previousTemplate;
            var globalId = postType + ":" + id + ":" + this.template;
            /* Save this template to session storage */
            if (globalId && blocks) {
                var value = serialize(blocks);
                sessionStorage.setItem(previousId, value);
            }
            /*
            If the user is switching back and forth between templates we want to save the data for each template
            The other version of the template is only saved for that session
            */
            var retrievedContent = sessionStorage.getItem(globalId);
            /* Retrieve template layout from the server */
            if (this.template !== DEFAULT) {
                if (this.template in TEMPLATES) {
                    var theTemplate = TEMPLATES[this.template].template;
                    if (theTemplate) {
                        var theBlocks = retrievedContent ? parse(retrievedContent) : blocks;
                        var shapedBlocks = synchronizeBlocksWithTemplate(theBlocks, theTemplate);
                        resetBlocks(shapedBlocks);
                        updatePost({ content: serialize(shapedBlocks) });
                        return;
                    }
                }
            }
            else {
                /*
                When returning to the default template just use the content that was already there
                  (Which in most cases will be nothing, unless you are manually entering post content from wordpress)
                */
                resetBlocks(retrievedContent ? parse(retrievedContent) : blocks);
            }
        }
    };
    return PageTemplateSwitcher;
}());
var templateSwitcher = new PageTemplateSwitcher();
templateSwitcher.init();
