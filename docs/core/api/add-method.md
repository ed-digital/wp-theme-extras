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
