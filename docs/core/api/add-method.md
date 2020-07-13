### **addMethod**

Adds a route to the ED rest API.

Methods can be called with `Site.callApi(methodName)` on the client.
`addMethod` routes only accept POST requests by default

```php
$callback = function ($data /* Post body */) {
  /* Result is encoded as json */
  return [
    'user' => true
  ];
};

ED()->API->addMethod(
  $methodName: string,
  $callback: function
);
```

---

#### Example

```php
// php
ED()->API->addMethod(
  'test/method',
  function () {
    return [
      'worked' => true
    ];
  }
);
```

```js
// js
let result = await Site.callApi("test/method")
/* result === { worked: true } */
```
