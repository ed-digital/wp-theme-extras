<?php

/* 
Helpers for using images with srcsets and bridging acf <-> wp image data
*/
if (!class_exists('image')) {
  class Image extends Element {
  
    /* The difference in aspect ratio that makes it look not square */
    public static $visual_diff_at = 0.1;
  
    /* How many times height fits into width */
    public static function aspect ($img) {
      return get($img, 'width', 1) / get($img, 'height', 1);
    }
  
  
    /* Square: 0.9 < aspect < 1.1 */
    public static function isSquare ($img) {
      $aspect = self::aspect($img);
      return $aspect > 1 - self::$visual_diff_at && $aspect < 1 + self::$visual_diff_at;
    }
    /* Landscape: 1.1 < aspect */
    public static function isLandscape ($img) {
      return self::aspect($img) > 1 + self::$visual_diff_at;
    }
    /* Portrait: aspect < 0.9 */
    public static function isPortrait ($img) {
      return self::aspect($img) < 1 - self::$visual_diff_at;
    }
  
    public static function orientation ($img) {
      if (self::isSquare($img)) {
        return 'square';
      } else if (self::isLandscape($img)) {
        return 'landscape';
      } else if (self::isPortrait($img)) {
        return 'portrait';
      } else {
        return 'unknown';
      }
    }

    public static function getImageSizes () {
      return get_intermediate_image_sizes();
    }
  
    public static function getSizesFromACF ($img) {
      $sizes = [];
      foreach ($img['sizes'] as $key => $val) {
        if (strpos($key, '-width') !== false) {
          $matches = [];
          preg_match("/(.*?)-width/", $key, $matches);
          $sizes[] = $matches[1];
        }
      }
      return $sizes;
    }
  
    /* Return wordpress image sizes as a srcset string */
    public static function getSrcset ($img, $sizes = null, $size = 'natural') {
      $originalURL = get($img, 'url');
      if (!$sizes) {
        $sizes = self::getImageSizes($img);
      }
      $baseAspect;
  
      if ($size === 'natural') {
        $baseAspect = get($img, 'width') / get($img, 'height');
      } else {
        $baseAspect = get($img, "sizes.$size-width") / get($img, "sizes.$size-height");
      }
      
      $result = [];
  
      $array = [];
  
      foreach ($sizes as $size) {
        $currentAspect = get($img, "sizes.$size-width") / get($img, "sizes.$size-height");
        $url = get($img, "sizes.$size");
        
        if (!self::aspectsAreDifferent($baseAspect, $currentAspect) && $url !== $originalURL) {
          $array[] = $url . ' ' . get($img, "sizes.$size-width") . 'w';
        }
      }

      $array[] = $originalURL . ' ' . get($img, "width") . 'w';
  
      $srcset = implode(',', $array);
    
      return $srcset;
    }
  
    public static function aspectsAreDifferent ($aspectA, $aspectB) {
      return abs($aspectA - $aspectB) > self::$visual_diff_at * 2;
    }
  
    /* Echo the srcset attribute */
    public static function srcset($img){
      $srcset = self::get_srcset($img);
      echo "srcset='$srcset'";
    }
  }
} else {
  error_log('Tried including helper "image" but the class already exists');
}