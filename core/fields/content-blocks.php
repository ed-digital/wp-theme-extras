<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('acf_field_contentblocks') ) :


class acf_field_contentblocks extends acf_field {


	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function __construct() {

		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/

		$this->name = 'contentblocks';


		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/

		$this->label = __('ED. Content Blocks', 'acf-contentblocks');


		/*
		*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
		*/

		$this->category = 'layout';

		$this->defaults = array(
			'enabledTypes' => []
		);


		// do not delete!
    	parent::__construct();

	}


	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field_settings( $field ) {

		/*
		*  acf_render_field_setting
		*
		*  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
		*  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
		*
		*  More than one setting can be added by copy/paste the above code.
		*  Please note that you must also have a matching $defaults value for the field name (font_size)
		*/

		// Grab the content types field post
		$contentTypesPost = get_posts([
			'name' => 'field_blocks',
			'post_type' => 'acf-field'
		]);

		$types = [];

		if($contentTypesPost && count($contentTypesPost)) {
			$data = unserialize($contentTypesPost[0]->post_content);
			if($data && $data['layouts']) {
				foreach($data['layouts'] as $item) {
					$types[$item['name']] = $item['label'];
				}
			}
		}

		acf_render_field_setting( $field, array(
			'label'			=> __('Enabled Types','acf-contentblocks'),
			'instructions'	=> "Select the block types that should be enabled for this field",
			'type'			=> 'checkbox',
			'choices'		=> $types,
			'name'			=> 'enabledTypes'
		));

	}

	function getOriginalField($postID = null) {
		$selector = "field_blocks";
		$post_id = false;
		$format_value = true;
		$load_value = true;

		// compatibilty
		if( is_array($format_value) ) extract( $format_value );

		// get valid post_id
		$post_id = acf_get_valid_post_id( $post_id );

		// get field key
		$field = acf_maybe_get_field( $selector, $post_id, false);

		// bail early if no field found
		if( !$field ) return false;

		// load value
		if( $load_value ) {
			$field['value'] = acf_get_value( $post_id, $field );
		}

		// format value
		if( $format_value ) {
			// get value for field
			$field['value'] = acf_format_value( $field['value'], $post_id, $field );
		}


		// return
		return $field;
	}


	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/

	function render_field( $field ) {


		/*
		*  Review the data of $field.
		*  This will show what data is available
		*/
		?>

		<div class="acf-field acf-field-flexible-content" data-name="<?=$field['name'];?>" data-type="flexible_content" data-key="<?=$field['key']?>">

			<?

				$contentTypesPost = get_posts([
					'name' => 'field_blocks',
					'post_type' => 'acf-field'
				]);

				if(!$contentTypesPost || !count($contentTypesPost)) return;

				global $post;

				$originalField = $this->getOriginalField();

				$blockTypes = [];

				foreach($originalField['layouts'] as $item) {
					if(in_array($item['name'], $field['enabledTypes'])) {
						$blockTypes[] = $item;
					}
				}

				$flexibleContentProxy = [
					'key' => $field['key'],
					'name' => $field['name'],
					'id' => $field['id'],
					'type' => 'flexible_content',
					'value' => $field['value'],
					'instructions' => $field['instructions'],
					'required' => $field['required'],
					'conditional_logic' => $field['conditional_logic'],
					'wrapper' => $field['wrapper'],
					'button_label' => 'Add Block',
					'layouts' => $blockTypes
				];

				do_action('acf/render_field/type=flexible_content', $flexibleContentProxy);

			?>
		</div>
		<?php
	}


	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	function input_admin_enqueue_scripts() {

		wp_enqueue_script('acf-pro-input');

	}



	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	function update_value( $value, $post_id, $field ) {

		$originalField = $this->getOriginalField($post_id);

		$originalField['key'] = $field['key'];
		$originalField['name'] = $field['name'];
		$originalField['ID'] = $field['ID'];

		return apply_filters('acf/update_value/type=flexible_content', $value, $post_id, $originalField);

	}


	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/

	function format_value( $value, $post_id, $field ) {

		$originalField = $this->getOriginalField();

		$originalField['key'] = $field['key'];
		$originalField['name'] = $field['name'];
		$originalField['ID'] = $field['ID'];

		return apply_filters('acf/format_value/type=flexible_content', $value, $post_id, $originalField);
	}

	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/

	function load_value( $value, $post_id, $field ) {

		$originalField = $this->getOriginalField();

		$originalField['key'] = $field['key'];
		$originalField['name'] = $field['name'];
		$originalField['ID'] = $field['ID'];

		//dump("Load");
		return apply_filters('acf/load_value/type=flexible_content', $value, $post_id, $originalField);

	}


	/*
	*  validate_value()
	*
	*  This filter is used to perform validation on the value prior to saving.
	*  All values are validated regardless of the field's required setting. This allows you to validate and return
	*  messages to the user if the value is not correct
	*
	*  @type	filter
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$valid (boolean) validation status based on the value and the field's required setting
	*  @param	$value (mixed) the $_POST value
	*  @param	$field (array) the field array holding all the field options
	*  @param	$input (string) the corresponding input name for $_POST value
	*  @return	$valid
	*/

	function validate_value($valid, $value, $field, $input) {

		$originalField = $this->getOriginalField();

		$originalField['key'] = $field['key'];
		$originalField['name'] = $field['name'];
		$originalField['ID'] = $field['ID'];

		$result = apply_filters('acf/validate_value/type=flexible_content', $valid, $value, $originalField, $input);

		return $result;

	}


	/*
	*  delete_value()
	*
	*  This action is fired after a value has been deleted from the db.
	*  Please note that saving a blank value is treated as an update, not a delete
	*
	*  @type	action
	*  @date	6/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (mixed) the $post_id from which the value was deleted
	*  @param	$key (string) the $meta_key which the value was deleted
	*  @return	n/a
	*/

	function delete_value( $post_id, $key, $field ) {

		return do_action('acf/delete_value/type=flexible_content', $post_id, $key, $field);

	}


}


// create initialize
new acf_field_contentblocks();


// class_exists check
endif;

?>