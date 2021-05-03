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

  function processGutenbergBlock($block, $parents = []) {

    // Embed code
    if ($block['blockName'] === 'core/embed') {
      // Generate embed code
      $block['attrs']['code'] = @wp_oembed_get($block['attrs']['url']);
      
      // Extra width/height from the embed code
      if (preg_match_all("/(width|height)=\"([0-9]+)\"/", $block['attrs']['code'], $matches)) {
        if (count($matches[0]) == 2) {
          $block['attrs'][$matches[1][0]] = $matches[2][0];
          $block['attrs'][$matches[1][1]] = $matches[2][1];
          $block['attrs']['ratio'] = $block['attrs']['width'] / $block['attrs']['height'];
        }
      }

      // Extract the caption
      if (@preg_match("/<figcaption>(.+)<\/figcaption>/", $block['innerHTML'], $match)) {
        $block['attrs']['caption'] = $match[1];
      }
    } else if (preg_match("/^acf\//", $block['blockName'])) {
      $blockID = "block_".rand(0, 100000);
      $attrs = $block['attrs'];
      $type = acf_get_block_type($block['blockName']);
      acf_setup_meta($attrs['data'], $blockID, true);
      unset($attrs['id']);
      unset($attrs['name']);
      unset($attrs['data']);
      $data = get_fields();
      if ($type && $type['process']) {
        $data = $type['process']($data);
      }
      $block['attrs'] = @array_merge($attrs, $data);
      acf_reset_meta($blockID);
    }

    $block['isTopLevel'] = count($parents) == 0;

    return $block;
  }

  function isInlineGutenbergBlock($block) {
    return preg_match("/^core\//", $block['blockName']);
    // return in_array($block['blockName'], [
    //   'core/paragraph',
    //   'core/list',
    //   'core/heading',
    //   'core/embed'
    // ]);
  }

  function collapseGutenbergBlocks($ogBlocks, $topLevel = false) {
    $blocks = [];
    $parent = null;
    foreach ($ogBlocks as $block) {
      if (isInlineGutenbergBlock($block) && $topLevel) {
        if (!$parent) {
          $parent = [
            'blockName' => 'layout/grouped-content',
            'innerBlocks' => [],
            'attrs' => [
              'isTopLevel' => $topLevel
            ]
          ];
        }
        $parent['innerBlocks'][] = $block;
      } else {
        if ($parent) {
          $blocks[] = $parent;
          $parent = null;
        }
        $blocks[] = $block;
      }
    }
    if ($parent) {
      $blocks[] = $parent;
      $parent = null;
    }
    return $blocks;
  }

  function walkAndProcessGutenbergBlocks($blocks, $parents = []) {
    // Filter out empty blocks
    $blocks = array_filter($blocks, function($block) {
      if (!$block['blockName'] && preg_match("/^\s*$/", $block['innerHTML'])) {
        // Empty block
        return false;
      } else {
        return true;
      }
    });

    // Collapse core inline content (paragraphs, lists, headings etc) into a parent
    $blocks = collapseGutenbergBlocks($blocks, count($parents) == 0);
    // dump($blocks);
    // die();

    // Process each block, and it's children if applicable
    return array_map(function($block) use($parents) {
      $block = processGutenbergBlock($block, $parents);
      if (is_array($block['innerBlocks']) && count($block['innerBlocks'])) {
        $block['innerBlocks'] = walkAndProcessGutenbergBlocks($block['innerBlocks'], array_merge($parents, [$block]));
      }
      return $block;
    }, $blocks);
  }

  function parseAndProcessGutenbergContent($content) {
    $parsed = @parse_blocks($content);
    $processed = walkAndProcessGutenbergBlocks($parsed);

    return $processed;
  }