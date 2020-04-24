<?

class JSON {
  public static function stringify ($arg) {
    try {
      return json_encode($arg, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
      if ($e->getCode() === 6) {
        return JSON::stringify(JSON::remove_recursion($arg));
      }
    }
  }
  public static function parse ($arg) {
    return json_decode($arg);
  }
  public static function remove_recursion(&$object, &$stack = array()) {
    if ((is_object($object) || is_array($object)) && $object) {
      if (!in_array($object, $stack, true)) {
        $stack[] = $object;
        foreach ($object as &$subobject) {
          self::remove_recursion($subobject, $stack);
        }
      } else {
        $object = "*RECURSION*";
      }
    }
    return $object;
  }
}

/* Tests */
// $a = (object)[ 'val' => true ];
// $a->b = $a;
// $a->test = true;
// $result = JSON::stringify($a);
// dump($result);
// $b = [];
// $b[] = $b;
// $result = JSON::stringify($b);
// dump($result);
// exit;
