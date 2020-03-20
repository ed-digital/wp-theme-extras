<?php
class Element {
  public static function classes ($arr, $join = true) {
    $classes = [];
    foreach ($arr as $k => $v) {
      if ($v) {
        $classes[] = $v;
      }
    }
    
    return $join ? implode(' ', $classes) : $classes;
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
}