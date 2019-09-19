<?

  set_exception_handler(function($err) {
    if (error_reporting()) {
      echo "<pre style='font-size: 12px; color: white; background: red; display: inline-block; padding: 4px;'>\n";
      echo $err->__toString();
      echo "\n</pre><br>";
    }
  });