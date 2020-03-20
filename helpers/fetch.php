<?

/* 

Axios is a php implementation of the js library by the same name

$request = new Axios();
$request->post('<url>', $args);

if ($request->error) {
  throw new Error($request->error);
}

return $request->response;

*/

class Fetch {
  function __construct ($opts = []) {
    $this->ch = curl_init();
    $this->set('return_result', true);
    $this->set('follow_location', true);
    $this->set('throw_on_error', true);

    /* 
      CURL doesnt like self signed certificates when sending requests.
      So when requesting from local we allow dodgy ssl certificates
    */
    if (strpos(get($_SERVER, 'HTTP_HOST'), '.local') !== false) {
      $this->set('verify_ssl_security', false);
    }

    foreach ($opts as $k => $v) {
      $this->set($k, $v);
    }
  }

  function get ($url, $options = []) {
    
    $this->set('method', 'GET');
    $this->set('url', $url);
    $this->setQuery(get($options, 'query'));
    $this->setHeaders(get($options, 'headers'));
    
    $this->response = $this->send();
    
    try {
      $this->response = json_decode($this->response);
    } catch (Exception $e) {}
    
    $this->error = $this->get_error();
    $this->close();
  }

  function post ($url, $args = null, $opts = []) {
    $this->set('method', 'POST');
    $this->set('url', $url);
    $this->setData($args);
    $this->setHeaders(get($options, 'headers'));
    
    $this->response = $this->send();
    try {
      $this->response = json_decode($this->response);
    } catch (Execption $e) {}

    $this->error = $this->get_error();
    $this->close();
  }

  function send () {
    return curl_exec($this->ch);
  }

  function close () {
    curl_close($this->ch);
  }

  function get_error () {
    if (curl_error($this->ch)) {
      return curl_error($this->ch);
    }
  }

  /* Used for query string encoded data */
  function setQuery ($query) {
    if ($query) {
      $parts = [];
      foreach($query as $k => $v) {
        $parts[] = "$k=$v";
      }
      $queryStr = implode('&', $parts);

      $this->set('data', $parts);
    }
  }

  /* Used for json encoded data */
  function setData ($data) {
    if ($data) {
      $this->set('data', json_encode($data));
    }
  }

  function setHeaders ($headers = []) {
    if ($headers) {
      $headerParts = [];
      foreach ($headers as $k => $v) {
        $headerParts[] = "$k: $v";
      }

      $this->set('headers', $headerParts);
    }
  }

  /* Set curl options by our own custom array keys */
  function set ($option, $value) {
    $keys = [];

    switch ($option) {
      case 'url':
        $keys[CURLOPT_URL] = $value;
        break;
      case 'method':
        switch (strtolower($value)) {
          case 'put':
            $keys[CURLOPT_PUT] = true;
            break;
          case 'post':
            $keys[CURLOPT_POST] = true;
            break;
          case 'get':
            /* Get request is default */
            break;
          case 'delete':
            $keys[CURLOPT_DELETE] = true;
            break;
        }
      case 'throw_on_error':
        $keys[CURLOPT_FAILONERROR] = $value;
        break;
      case 'follow_location':
        $keys[CURLOPT_FOLLOWLOCATION] = $value;
        break;
      case 'return_result':
        $keys[CURLOPT_RETURNTRANSFER] = $value;
        break;
      case 'headers': 
        $keys[CURLOPT_HTTPHEADER] = $value;
        break;
      case 'data':
        $keys[CURLOPT_POSTFIELDS] = $value;
        break;
      case 'verify_ssl_security':
        $keys[CURLOPT_SSL_VERIFYPEER] = false;
        $keys[CURLOPT_SSL_VERIFYHOST] = false;
        break;
    }

    foreach ($keys as $k => $v) {
      curl_setopt($this->ch, $k, $v);
    }

    return $this;
  } 
}
?>