<?
if (!class_exists('CSS') && !class_exists('CSSUnitValue')) {

  class CSS {
    static $SECOND = 's';
    static $MILLISECOND = 'ms';
    static $REM = 'rem';
    static $EM = 'em';
    static $PX = 'px';
    static $VW = 'vw';
    static $VH = 'vh';
    static $PERCENT = '%';
  
    static function value ($unit) {
      if ($unit === CSS::$SECOND) {
        return 1000;
      }
      if ($unit === CSS::$MILLISECOND) {
        return 1;
      }
      return 1;
    }
  
    static function isTimeUnit($unit) {
      return $unit === CSS::$SECOND || $unit === CSS::$MILLISECOND;
    }
  }
  
  class CSSUnitValue {
    function __construct ($value, $unit) {
      if (!$unit) {
        if (is_numeric($value)) {
          $value = $value + 0;
          $unit = 'number';
        } else {
          $unit = 'string';
        }
      }
  
      $this->unit = $unit;
      $this->value = $value;
    }
  
    function to ($to) {
      $from = $this->unit;
      if (CSS::isTimeUnit($from)) {
        if (!CSS::isTimeUnit($to)) {
          throw new Error("Cannot convert from $from to $to");
        }
  
        $factor = CSS::value($from) / CSS::value($to);
  
        return new CSSUnitValue($this->value * $factor, $to);
      }
    }

    function getValue () {
      return $this->value;
    }
  
    static function parse ($value) {
      $matches = [];
      preg_match("/([0-9\.]*?)([a-zA-Z])/", $value, $matches);
  
      if (!isset($matches[1])) {
        throw new Error("Was not able to parse $value");
      }
  
      return new CSSUnitValue($matches[1], $matches[2]);
    }
  }
} else {
  error_log('Tried including helpers "CSS" and "CSSUnitValue" but the classes already exists');
}