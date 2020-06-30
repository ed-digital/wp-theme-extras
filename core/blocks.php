<?php

  // use_block_editor_for_post()

  // add_filter("use_block_editor_for_post_type", function($can_edit, $post_type) use ($templateSettings) {
  //   if ($can_edit) {
  //     $templateName = @get_page_template_slug($_GET['post']);
  //     if ($templateName) {
  //       if (@$templateSettings[$templateName] && @$templateSettings[$templateName]['allowBlocks']) {
  //         return $can_edit;
  //       } else {
  //         // Default to false
  //         return false;
  //       }
  //     }
  //   }

  //   return $can_edit;
  // }, 10, 2);

  add_filter('allowed_block_types', function($types, $post) {
    // Get the current template, if one exists.
    $templateName = @get_page_template_slug($_GET['post']);
    if (!$templateName) $templateName = "default";

    // Get all block types declared to ACF
    $blockTypes = acf_get_block_types();
    $allowedBlocks = ED()->blockWhitelist;

    foreach ($blockTypes as $name => $def) {
      if (is_callable(@$def['test'])) {
        if (!$def['test']($post, $templateName)) {
          continue;
        }
      } else {
        if ($post->post_type === 'page') {
          if (@is_array($def['templates']) && @!in_array($templateName, $def['templates'])) {
            // Don't allow this block, since the current template is not on the whitelist
            continue;
          }
        }
        if (is_array($def['post_types']) && count($def['post_types']) && @!in_array($post->post_type, $def['post_types'])) {
          // Don't allow this block, since the current post type is not on the whitelist
          continue;
        }
      }
      $allowedBlocks[] = $name;
    }

    return $allowedBlocks;
  }, 2, 3);
