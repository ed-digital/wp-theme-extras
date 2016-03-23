jQuery(function($) {
	
	// Triggers the 'render' function of the Flexible Content field, which adds the much needed parent_layout field
	var editor = $('.content-blocks-editor .acf-field-object-flexible-content:first');
	if(editor.size()) {
		acf.do_action('open_field', editor);
		console.log("Triggered render function of flexble content field.");
	}
	
	// Copy the flexible_content field to contentblocks. 
	if(window.acf) {
		acf.fields.contentblocks = acf.fields.flexible_content;
	}
	
	// Remove content block types ACF group from the table, since it should only be edited via the 'Content Blocks' UI. Not that it actually matters.
	$("#acf-field-group-wrap .wp-list-table tr").each(function() {
		var postName = $(this).find('.post_name').text();
		if(postName === 'group_sharedblocks') {
			$(this).remove();
		}
	})
});