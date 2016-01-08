<?
	
	function add_acf_status_column($columns) {
		
		if(!isset($_GET['post_status']) || $_GET['post_status'] !== "sync") {
			
			$columns = array(
				'cb'	 			=> '<input type="checkbox" />',
				'title' 			=> "Title",
				'ed_acf_status' 	=> "Source",
				'fields' 			=> "Fields",
			);
		
		}
		
		return $columns;
	}
	
	function print_acf_status_column($column, $postID = null) {
		
		if($column == 'ed_acf_status') {
			
			global $post;
			
			$ed = ED();
			
			foreach(acf_get_field_groups() as $group) {
				if($group['ID'] !== $post->ID) continue;
				
				$source = isset($ed->moduleFieldGroups[$post->post_name]) ? $ed->moduleFieldGroups[$post->post_name] : null;
				
				if(!$source) {
					echo "&mdash;";
					return;
				}
				
				$originalDate = $source->modified;
				
				$modifiedUsingUI = !isset($group['fresh']);
				$modifiedSource = $group['original_import_modified_date'] != $originalDate;
				
				$message = "";
				
				echo "Imported from ThemED / ".$ed->moduleTitle($source->module);
				
				$buttonMessage = "Re-import from ThemED";
				
				if($modifiedUsingUI && $modifiedSource) {
					$message = "The original file this field group was imported from has since been modified, <strong>PLUS</strong>, the field group has been manually edited using the UI since it was initially imported. Only re-import if you really want to use the latest version.";
					$buttonMessage = "Scrap changes and re-import from ThemED";
				} else if($modifiedUsingUI) {
					$message = "This field group has been edited using the UI since it was initially imported. Only re-import if you really want to revert to the original version.";
					$buttonMessage = "Revert to original ThemED version";
				} else if($modifiedSource) {
					$message = "The original file this field group was imported from has since been modified.";
					$buttonMessage = "Re-import from ThemED";
				}
				
				if($message) {
					echo "<br><small style='color: #cc0000'>".$message."</small>";
					
				} else {
					echo "<br><small>Everything is up to date.</small>";
					$buttonMessage = "Re-import from ThemED anyway";
				}
				
				echo "<br><small><a href='edit.php?post_type=acf-field-group&reimport=".$post->ID."&group_key=".$group['key']."'>".$buttonMessage."</a></small>";
				
			}
            
	    }
	    
	}
	
	add_filter('manage_edit-acf-field-group_columns', 'add_acf_status_column', 15);
 	add_action('manage_acf-field-group_posts_custom_column', 'print_acf_status_column', 15);
	
?>