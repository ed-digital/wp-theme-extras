<?

/*
  Some helper methods for colors 
*/
if (!class_exists('Color')) {

  function color_type ($color = [0, 0, 0, 1]) {
    if (is_array($color)) {
      return 'rgb_array';
    } else if (strpos($color, '#') === 0) {
      return 'hex';
    } else if (strpos($color, 'rgb') === 0) {
      return 'rgb';
    } else if (strpos($color, 'hsl') === 0) {
      return 'hls';
    }
  }

  function color_percent_to_decimal ($percent) {
    if (strpos($percent, '%') !== 'false') {
      return @(substr($percent, 0, -1) / 100);
    } else {
      return $percent;
    }
  }

  /* Always stores colors as rgba */
  class Color {
    function __construct($color = "#000000"){
      $rgbaArray = Color::convert_to_rgba_array($color);
      $this->r = $rgbaArray[0];
      $this->g = $rgbaArray[1];
      $this->b = $rgbaArray[2];
      $this->a = $rgbaArray[3];
    }

    static function convert_to_rgba_array ($color) {
      $type = color_type($color);
      /* Always convert the color to an rgb array */
      switch ($type) {
        case "rgb_array": {
          return [
            @$color[0] ?? 0,
            @$color[1] ?? 0,
            @$color[2] ?? 0,
            @$color[3] ?? 1
          ];
        }
        case "rgb": {
          $parts = Arr::map(
            explode(", ", preg_replace("/^rgba?\\\(\\\)$/", "", $color)),
            function ($item) {
              dump($item);
              return @(+(trim($item)));
            }
          );

          return Color::convert_to_rgba_array($parts);
        }
        case "hex": {
          $hex = str_replace('#', '', $color);
          $length = strlen($hex);
      
          $r = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
          $g = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
          $b = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));

          return [$r, $g, $b, 1];
        }
        case "hls": {
          $parts = Arr::map(
            explode(", ", preg_replace("/^hsl\\\(.*?\\\)$/", "", $color)),
            function ($item) {
              return color_percent_to_decimal(trim($item));
            }
          );
          $h = $parts[0];
          $s = $parts[1];
          $l = $parts[2];

          $r; 
          $g; 
          $b;
      
          $c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
          $x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
          $m = $l - ( $c / 2 );
        
          if ( $h < 60 ) {
            $r = $c;
            $g = $x;
            $b = 0;
          } else if ( $h < 120 ) {
            $r = $x;
            $g = $c;
            $b = 0;			
          } else if ( $h < 180 ) {
            $r = 0;
            $g = $c;
            $b = $x;					
          } else if ( $h < 240 ) {
            $r = 0;
            $g = $x;
            $b = $c;
          } else if ( $h < 300 ) {
            $r = $x;
            $g = 0;
            $b = $c;
          } else {
            $r = $c;
            $g = 0;
            $b = $x;
          }
        
          $r = ( $r + $m ) * 255;
          $g = ( $g + $m ) * 255;
          $b = ( $b + $m ) * 255;
        
          return [floor($r), floor($g), floor($b), 1];
        }
      }
    }

    function hex () {
      return '#' . dechex($this->r) . dechex($this->g) . dechex($this->b);
    }

    function rgb () {
      return "rgba({$this->r}, {$this->g}, {$this->b})";
    }

    function hls () {
      $oldR = $r;
      $oldG = $g;
      $oldB = $b;

      $r /= 255;
      $g /= 255;
      $b /= 255;

      $max = max( $r, $g, $b );
      $min = min( $r, $g, $b );

      $h;
      $s;
      $l = ( $max + $min ) / 2;
      $d = $max - $min;

      if( $d == 0 ){
        $h = $s = 0; // achromatic
      } else {
        $s = $d / ( 1 - abs( 2 * $l - 1 ) );
        switch( $max ){
          case $r:
            $h = 60 * fmod( ( ( $g - $b ) / $d ), 6 ); 
            if ($b > $g) {
              $h += 360;
            }
            break;
          case $g: 
            $h = 60 * ( ( $b - $r ) / $d + 2 ); 
            break;
          case $b: 
            $h = 60 * ( ( $r - $g ) / $d + 4 ); 
            break;
        }	        
      }

      return "hsl(".round( $h, 2 ).", ".round( $s, 2 ).", ".round( $l, 2 ).")";
    }
  }
} else {
  error_log('Tried including helper "color" but the class already exists');
}
?>