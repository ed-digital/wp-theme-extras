<?

/* 
Get a property from an object or array. Use "." separated keys to get nested values. 
Works for nested arrays and objects. The third argument can be used for a default value when 
no value is found

eg:
$data = (object)[
  'value' => [
    'current' => (object)[
      'test' => true
    ]
  ]
];

$val = get($data, 'value.current.test');

// $val === true

$val = get($data, 'not_there', 'default');
// $val === 'default';

$val = get($data);
// $val == $data;
*/

if (!function_exists('get')) {
  function get($obj, $index = '', $default = null) {
    $target = $obj;
  
    /*
      Return the original $arr if there is no index
    */
    if ($index === null || $index === '' || !isset($index)) {
      return $obj;
    }
  
    $paths = explode('.', $index);
    $length = count($paths);
  
  
    while ($length) {
      /* Remove the next key from paths */
      $key = array_shift($paths);
      $length--;
  
      if (is_array($target) && isset($target[$key])) {
        $target = $target[$key];
      } else if (is_object($target) && @$target->{$key}) {
        $target = $target->{$key};
      } else {
        $target = null;
        break;
      }
    }
  
    return $target === null ? $default : $target;
  }

  if (!function_exists('set')) {
    function set ($obj, $key, $value) {
      $target = &$obj;
      $original = &$target;
      
      $parts = is_array($key) ? $key : explode('.', $key);
      $length = count($parts);
      
      for ($i = 0; $i < $length - 1; $i++) {
        $key = $parts[$i];
        $current = get($target, $key);
        if (!$current || !is_array($current) || !is_object($current)) {
          if (is_object($target)) {
            $target->$key = (object)[];
            $target = $target->$key;
          } else {
            $target[$key] = [];
            $target = &$target[$key];
          }
        }
      }
      if (is_object($target)) {
        $target->{$parts[$i]} = $value;
      } else {
        $target[$parts[$i]] = $value;
      }

      return $original;
    }
  }

  /* 
  Returns a get function with the first method bound to a specific object 
  */
  if (!function_exists('createGetter')) {
    function createGetter ($object) {
      return function ($propDeep = '', $default = null) use ($object) {
        return get($object, $propDeep, $default);
      };
    }
  }
} else {
  error_log('Tried including the "get" helpers" but they already exist');
}


?>