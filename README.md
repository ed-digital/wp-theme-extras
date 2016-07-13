# ED. Wordpress Plugin

## Lets document this

## Underscore.php
The ED. plugin now auto loads the underscore.php library.
You can find the full documentation [here](http://brianhaveri.github.io/Underscore.php/).
The main ones to be aware of are:

```php
__::map(array(1, 2, 3), function($num) { return $num * 3; });
__::filter(array(1, 2, 3, 4), function($num) { return $num % 2 === 0; }); 
__::reduce(array(1, 2, 3), function($memo, $num) { return $memo + $num; }, 0);
```
