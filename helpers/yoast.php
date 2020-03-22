<?

class Yoast {
  static function meta ($id = null) {
    global $post;
    if ($id === null) {
      $id = $post->ID;
    }

    return get_post_meta($id, '_yoast_wpseo_metadesc', true);
  }
}

?>