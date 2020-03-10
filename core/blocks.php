<?php

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
    $templateName = @get_page_template_slug($_GET['post']) ?? "";

    // Get all block types declared to ACF
    $blockTypes = acf_get_block_types();
    $allowedBlocks = [];

    foreach ($blockTypes as $name => $def) {
      if (is_array($def['templates']) && !in_array($templateName, $def['templates'])) {
        continue;
      }
      $allowedBlocks[] = $name;
    }

    return $allowedBlocks;
  }, 1, 3);
