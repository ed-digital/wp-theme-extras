# The `Fetch` helper

The Fetch helper can be used to create requests from wordpress

### Make a get request

Fetches are configured as get requests by default so that means you can just pass the url in as the first argument to fetch and it will send a get request to that url

```php
$request = fetch('https://jsonplaceholder.typicode.com/todos/2');

if ($request->error) {
  /* Handle error case */
} else {
  return $request->data;
}
```

### Make a post request

```php
$request = fetch(
  'https://jsonplaceholder.typicode.com/todos/2',
  [
    'method' => 'post',
    'body' => [
      'foo' => true,
      'bar' => false
    ]
  ]
);
```

There is also a short hand way of specifying a post request

```php
fetch()->post(
  'https://jsonplaceholder.typicode.com/todos/2',
  [
    'foo' => true,
    'bar' => false
  ],
  [ /* opts */ ]
);
```

### Making multiple requests in parallel

Fetch comes with a utility for writing parallel requests called `Fetch::multi`. multi captures all fetch calls inside it's callback and sends them once the function has ended. It returns all the fetch objects in an array

```php
$requests = Fetch::multi(function () {
  foreach (['example.com', 'ed.com.au'] as $host) {
    fetch("https://$host/todos/2");
    fetch()->post("https://$host/todos/2");
  }
});
```

---

### Long form way of writing a fetch request

```php
<?
// GET
$request = new Fetch([
  'method' => "get",
  'url' => "url",
  'query' => [],
  'headers' => [
    'Authorization' => 'Basic '
  ]
]);
$request->send();
$request->error;
$request->response;
// POST
$request = new Fetch([
  'method' => "post",
  'url' => "url",
  'body' => [],
  'headers' => [
    'Authorization' => 'Basic '
  ]
]);
$request->send();
$request->error;
$request->response;
?>
```
