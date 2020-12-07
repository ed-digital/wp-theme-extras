<?php
/* 
Helper methods for interacting with "dom" elements (as strings)

Element::classes accepts an array and turns it into classes.
eg. Element::classes([ 'enabled' => true, 'disabled' => false ])
      -> "enabled"
    Element::classes(['cls-a', 'cls-b'])
      -> 'cls-a cls-b'
    Element::classes([])

Element::fixAspect takes a width and height param and 
returns the appropriate padding-bottom for an element
  eg <div style="<?= Element::fixAspect(1, 1) ?>"></div>
    -> <div style="padding-bottom: 100%;"></div>

Element::attrs adds attributes to an element
  eg. <div <?= Element::attrs([ 'data-widget="image"' => false, 'sizes' => 128 ]) ?>></div>
    -> <div sizes="128"></div>

Element::getAttribute is used to extract attributes from a domString
  eg. Element::getAttribute("<iframe src="google.com"></iframe>", 'src');
    -> "google.com"
*/

if (!class_exists('Element')) {
class Element {
  public static function classes (...$arrs) {
    $classes = [];
    
    foreach ($arrs as $arr) {
      foreach ($arr as $k => $v) {
        if (is_numeric($k)) {
          $classes[] = $v;
        } else if ($v) {
          $classes[] = $k;
        }
      }
    }
    
    return implode(' ', $classes);
  }

  public static function fixAspect ($width, $height) {
    if (is_dev()) {
      return "padding-bottom: calc(($height / $width) * 100%);";
    }
    return "padding-bottom: " . $height / $width * 100 . "%";
  }

  public static function attrs ($arr) {
    if (!$arr) return "";
    $vals = [];

    foreach ($arr as $k => $v) {
      if ($v === true) {
        $vals[] = $k;
      } elseif ($v === false) {
        continue;
      } else {
        $vals[] = "$k='$v'";
      }
    }

    return implode(' ', $vals);
  }

  public static function getAttributes ($element, $attributes) {
    $arr = [];
    foreach ($attributes as $attr) {
      $arr[$attr] = Element::getAttribute($element, $attr);
    }
    return $arr;
  }

  public static function getAttribute ($element, $attribute) {
    /* 
    
    preg_match("/\<.*?( .*?)\>/", $element, $m);
    */

    $attributes = explode(" ", $element);

    foreach ($attributes as $attr) {
      $pair = explode("=", $attr);
      $key = $pair[0];
      $val = $pair[1];
      
      if ($key === $attribute) {
        /*
        Trim quotes, space, tab, empty chars, and newlines
        */
        $value = trim($val, "\"' \0\x0B\r\n\t");

        /* Convert a booleanlike string */
        if ($value === 'true') {
          return true;
        } elseif ($value === 'false') {
          return false;
        }

        /* Convert a numberlike string */
        if (is_numeric($value)) {
          return strval($value);
        }

        /* 
        Convert a jsonlike string
        */
        $json = json_decode($value);
        if ($json) {
          return $value;
        }

        return $value;
      }
    }
  }
}}