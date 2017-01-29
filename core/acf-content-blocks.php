<?
	
	class ACFSharedFields {
		
		static $instance;
		
		static function getInstance() {
			
			if(!self::$instance) {
				self::$instance = new ACFSharedFields();
			}
			
			return self::$instance;
			
		}
		
		function __construct() {
			
			add_action('admin_menu', [$this, 'admin_menu']);
			
			// Add new field type
			add_action('acf/include_field_types', 	array($this, 'include_field_types'));
			add_action('acf/register_fields', 		array($this, 'include_field_types'));
			
			self::$instance = $this;
			
		}

		function admin_menu() {
			
			$this->ensureFieldsPostExists();
			
			ED()->addCSS("/assets/admin/css/content-blocks-editor.less");
			ED()->addJS("/assets/admin/js/acf-hacks.js");
			
			add_submenu_page("edit.php?post_type=acf-field-group", "Content Blocks", "Content Blocks", "install_plugins", "custom", [$this, 'show_page']);
			
			if(isset($_GET['post_type']) && $_GET['post_type'] === "acf-field-group") {
				
				if(isset($_GET['page']) && $_GET['page'] === 'custom') {
					wp_enqueue_style('acf-global');
					wp_enqueue_style('acf-input');
					wp_enqueue_style('acf-field-group');
					wp_enqueue_style('acf-pro-input');
					wp_enqueue_style('acf-pro-field-group');
					
					wp_enqueue_script('acf-input');
					wp_enqueue_script('acf-field-group');
					wp_enqueue_script('acf-pro-field-group');
					wp_enqueue_script('acf-pro-input');
					
					if(isset($_POST['action']) && is_admin()) {
						do_action($_POST['action']);
						die();
					}
				}
			
			}
			
		}
		
		function include_field_types() {
			include_once("fields/content-blocks.php");
		}
		
		function getPostContent() {
			return 'a:8:{s:8:"location";a:1:{i:0;a:1:{i:0;a:3:{s:5:"param";s:9:"post_type";s:8:"operator";s:2:"==";s:5:"value";s:4:"none";}}}s:8:"position";s:6:"normal";s:5:"style";s:7:"default";s:15:"label_placement";s:3:"top";s:21:"instruction_placement";s:5:"label";s:14:"hide_on_screen";s:0:"";s:11:"description";s:0:"";s:29:"original_import_modified_date";N;}';
		}
		
		function ensureFieldsPostExists() {
			
			global $wpdb;
			
			$fieldsPost = $this->getFieldsPost();
			
			if($fieldsPost->post_title == 'Shared Content Blocks') {
				// dump($wpdb->prepare("UPDATE $wpdb->posts SET post_content = %s, post_title = %s WHERE ID = ".$fieldsPost->ID, $this->getPostContent(), 'Shared Content Types'));
				// die();
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_content = %s, post_title = %s WHERE ID = ".$fieldsPost->ID, $this->getPostContent(), 'Shared Content Types'));
			}
			
			if($fieldsPost == null) {
				
				// Add field group
				$postID = wp_insert_post([
					'post_author' => 0,
					'post_title' => 'Shared Content Blocks',
					'post_name' => 'group_sharedblocks',
					'post_status' => 'publish',
					'post_content' => $this->getPostContent(),
					'post_type' => 'acf-field-group'
				]);
				
				// Add flexible content field
				wp_insert_post([
					'post_author' => 0,
					'post_content' => $postContent,
					'post_title' => 'Content Block Types',
					'post_excerpt' => 'block_types',
					'post_name' => 'field_blocks',
					'post_parent' => $postID,
					'post_status' => 'publish',
					'post_type' => 'acf-field'
				]);
				
			}
			
		}
		
		function getFieldsPost() {
			
			$posts = get_posts([
				'name' => 'group_sharedblocks',
				'post_type' => 'acf-field-group'
			]);
			
			if($posts && count($posts)) {
				return $posts[0];
			} else {
				return null;
			}
			
		}
		
		function show_page() {
			
			// include(get_home_path()."wp-content/plugins/advanced-custom-fields-pro/admin/field-group.php");
			
			global $post;
			
			$post = $this->getFieldsPost();
			
			$editor = new acf_admin_field_group();
			
			$saved = false;
			
			if(isset($_POST['saveFields']) && $_POST['saveFields'] === '1') {
				
				$_POST['acf_field_group'] = [
					'ID' => $post->ID,
					'title' => "Content Block Types",
					'key' => "group_sharedblocks"
				];
				
				$editor->save_post($post->ID, $post);
				
				$saved = true;
				
			}
			
			?>

			<div class="wrap content-blocks-editor">
				<h1>Content Block Types</h1>
				
				<? if($saved): ?>
					<div class="notice notice-success is-dismissible">
				        <p>Changes Saved!</p>
				    </div>
				<? endif; ?>
				
				<form method="post">
					<input type="hidden" name="saveFields" value="1">
			
					<div id="poststuff">
						<div id="post-body" class="metabox-holder columns-2">
							
							<div id="postbox-container-1" class="postbox-container submit-box">
								<div class="postbox">
									<div>
										<input type="submit" class="button button-primary button-large" value="Save Changes">
									</div>
								</div>
							</div>
							
							<div id="#postbox-container-2" class="postbox-container">
								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div class="postbox">
										<div class="inside content-blocks-editor-inner">
											
											<?
												$editor->current_screen();
												$editor->admin_head();
												
												// global
												global $field_group;
												
												// get fields
												$view = array(
													'fields' => acf_get_fields_by_id( $field_group['ID'] )
												);
												
												// load view
												acf_get_view('field-group-fields', $view);
												
												do_action('edit_form_after_title');
												
												$editor->admin_footer();
											?>
											
										</div>
									</div>
								</div>
							</div>
							
						</div>
					</div>
				</form>
			</div>
			
			<?
			
		}
		
	}
	
	new ACFSharedFields();

?>