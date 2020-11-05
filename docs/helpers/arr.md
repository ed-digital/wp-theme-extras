# Arr Helpers

`Arr` is a namespace of common array methods. Similar to the array_methods except with a consistent parameter order

> Array methods will always accept the subject array before the rest of the parameters

### **`Arr::map`**

```php
Arr::map($array, $callback)
```

Maps through an array calling the callback with each item, it's index and the complete array

#### Example

```php
<?
Arr::map(
  [10, 9, 8],
  function ($item, $index, $arr) {
    return $item - $index * 2;
  }
) === [20, 16, 14]
?>
```

### **`Arr::split`**

```php
Arr::split($array, $callback): [falsey[], truthy[]]
```

Filters through an array calling the callback with each item, if the callback returns true it is added to the second result array, if it's false it is added to the first result array

#### Example

```php
<?
Arr::split(
  [10, 9, 8],
  function ($item, $index, $arr) {
    return !($item % 2);
  }
) === [[9], [10, 8]]
?>
```
