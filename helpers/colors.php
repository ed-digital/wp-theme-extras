<?
if (!class_exists('Colors')) {
  class Colors {
    static function rgbToHex ($rgb) {
      $res = '#';
  
      foreach ($rgb as $color) {
        $res .= dechex($color);
      }
  
      return $res;
    }
  
    static function hexToRgb ($hex) {
      $hex = str_replace('#', '', $hex);
      $length = strlen($hex);
  
      $rgb = [];
      $rgb[] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
      $rgb[] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
      $rgb[] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
      
      return $rgb;
    }
  }
}
?>