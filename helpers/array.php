<?php
/* 
Some standard array methods with a little bit extra

(bridges the gap between wordpress array methods and JS array methods)
*/

if (!class_exists('Arr')) {
  class Arr {

    public static function map ($arr, $fn) {
      $res = [];
      foreach ($arr as $k => $item) {
        $res[$k] = $fn($item, $k, $arr);
      }
      return $res;
    }
  
    public static function join($arr, $str = '') {
      return implode($str, $arr);
    }

    public static function filter($arr, $predicate) {
      $result = [];
      foreach ($arr as $key => $value) {
        if ($predicate($value, $key, $arr)) {
          $result[] = $value;
        }
      }
      return $result;
    }
  
    public static function last ($arr) {
      return $arr[count($arr) - 1];
    }

    public static function merge ($arr, ...$arrs) {
      return array_merge($arr, $arrs);
    }
  
    public static function reduce($arr, $fn, $start) {
      $last = $start;
      foreach ($arr as $k => $v) {
        $last = $fn($last, $v, $k, $arr);
      }
      return $last;
    }
    
    public static function includes ($arr, $value) {
      return in_array($value, $arr);
    }
  }
}