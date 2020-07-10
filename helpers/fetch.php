<?

/* 

Fetch is a class that makes it MUCH easier to send requests from php
(and standardises all the different ways you can do a request in php)

$request = new Fetch();
$request->post('<url>', $args);

if ($request->error) {
  throw new Error($request->error);
}

return $request->response;

*/

if (!class_exists('fetch')) {

  class FetchQuery {
    function __construct ($param) {
      if (is_string($param)) {
        $this->params = $this->parseString($param);
      } elseif (is_object($param)) {
        $this->params = (array)$param;
      } elseif (is_array($param)) {
        $this->params = $param;
      } else {
        $this->params = [];
      }
    }

    function parseString($param) {
      $result = [];
      $parts = explode('&', $param);
      foreach ($parts as $k => $p) {
        $group = explode('=', $p);
        $result[$group[0]] = JSON::parse($group[1]) ?? $group[1];
      }
      return $result;
    }

    function set($prop, $value) {
      if (!isset($value)) {
        if (is_array($prop)) {
          $this->params = $prop;
        } elseif (is_string($prop)) {
          $this->params = $this->parseString($prop);
        }
      } else {
        $this->params[$prop] = $value;
      }
      return $this;
    }

    function get($prop) {
      return $this->params[$prop];
    }

    function toString () {
      $parts = [];
      foreach($this->params as $k => $v) {
        $parts[] = "$k=$v";
      }
      if (count($parts)) {
        return "?" . implode("&", $parts);
      } else {
        return "";
      }
    }
  }

  class FetchUrl {
    function __construct ($url) {
      $parts = parse_url($url);

      $this->scheme = get($parts, 'scheme');
      $this->host = get($parts, 'host');
      $this->port = get($parts, 'port');
      $this->user = get($parts, 'user');
      $this->pass = get($parts, 'pass');
      $this->path = get($parts, 'path');
      $this->fragment = get($parts, 'fragment');
      $this->query = new FetchQuery(get($parts, 'query'));
    }

    function toString () {
      $scheme   = get($this, "scheme") ? $this->scheme . '://' : '';
      $host     = get($this, "host") ? $this->host : '';
      $port     = get($this, "port") ? ':' . $this->port : '';
      $user     = get($this, "user") ? $this->user : '';
      $pass     = get($this, "pass") ? ':' . $this->pass  : '';
      $pass     = ($user || $pass) ? "$pass@" : '';
      $path     = get($this, "path") ? $this->path : '';
      $query    = $this->query->toString();
      $fragment = get($this, "fragment") ? '#' . $this->fragment : '';
      return "$scheme$user$pass$host$port$path$query$fragment";
    }
  }

  class MultiFetch {

    function __construct ($requests = []) {
      $this->requests = array_merge(
        [],
        $requests
      );
    }

    function add (...$requests) {
      $this->requests = array_merge(
        $this->requests,
        $requests
      );
    }

    function send () {
      $controller = curl_multi_init();

      foreach ($this->requests as $req) {
        curl_multi_add_handle($controller, $req->ch);
      }

      do {
        curl_multi_exec($controller, $running);
      } while ($running > 0);

      foreach ($this->requests as $req) {
        $req->setResponse(
          curl_multi_getcontent($req->ch)
        );
      }

      curl_multi_close($controller);
      return $this->requests;
    }
  }

  class Fetch {
    static $multi_handle = false;

    function __construct ($optsOrURL = [], $opts = []) {
      $this->options = [
        'method' => 'get',
        'headers' => [],
        'body' => null
      ];

      if (is_string($optsOrURL)) {
        $this->setUrl($optsOrURL);
        $this->merge($opts);
        $this->send();
      } else {
        $this->merge($optsOrURL);
      }     
    }

    function merge ($opts) {
      $this->options = array_merge(
        $this->options,
        $opts
      );
    }

    static function multi ($callback) {
      $multi = new MultiFetch();
      Fetch::$multi_handle = $multi;

      $callback();

      return $multi->send();
    }

    function createCurl () {
      if ($this->ch) return $this;

      $this->ch = curl_init();
      $method = $this->method();
      $encoding = get($this->options, 'headers.Content-Type', null);
      $isFormEncoded = $encoding === "application/x-www-form-urlencoded";


      $this->merge([
        'return_result' => true,
        'follow_location' => true,
        'throw_on_error' => true
      ]);

      $config = [];

      if (!$this->url) {
        $this->url = new FetchURL($this->options['url']);
      }

      if ($method === 'get' && get($this->options, 'query')) {
        $this->url->query->set(get($this->options, 'query'));
      } elseif($method === 'post' && get($this->options, 'body') && $isFormEncoded) {
        $this->url->query->set(get($this->options, 'body'));
      }
      
      foreach (['scheme', 'host', 'port', 'user', 'pass', 'path'] as $prop) {
        if (isset($this->options[$prop])) {
          $this->url->set($prop, $this->options[$prop]);
        }
      }

      $config[CURLOPT_URL] = $this->url->toString();
      /* converts option[keys] to curl[keys] */
      foreach ($this->options as $option => $value) {
        if ($option === "method") {
          /* Make the method lowercase to check in the switch method */
          switch ($this->method()) {
            case 'put':
              $config[CURLOPT_PUT] = true;
              break;
            case 'post':
              $config[CURLOPT_POST] = true;
              break;
            case 'get':
              /* Get request is default */
              break;
            case 'delete':
              $config[CURLOPT_DELETE] = true;
              break;
          }
        } elseif ($option === 'throw_on_error') {
          $config[CURLOPT_FAILONERROR] = $value;
        } elseif ($option === 'follow_location') {
          $config[CURLOPT_FOLLOWLOCATION] = $value;
        } elseif ($option === 'return_result') {
          $config[CURLOPT_RETURNTRANSFER] = $value;
        } elseif ($option === 'headers') {
          $config[CURLOPT_HTTPHEADER] = $value;
        } elseif ($option === 'body') {  
          if ($method === 'post' && !$isFormEncoded) {
            if ($value) {
              $config[CURLOPT_POSTFIELDS] = json_encode($value);
            }
          }
        } elseif ($option === 'verify_ssl_security') {
          $config[CURLOPT_SSL_VERIFYPEER] = false;
          $config[CURLOPT_SSL_VERIFYHOST] = false;
        }
      }

  
      foreach ($config as $k => $v) {
        curl_setopt($this->ch, $k, $v);
      }
  
      return $this;
    }

    function method () {
      return strtolower($this->options->method);
    }

    function setUrl ($url) {
      if ($url instanceof FetchURL) {
        $this->url = $url;
      } elseif($url) {
        $this->url = new FetchURL($url);
      }
    }
  
    function get ($url, $options = []) {
      $this->options['method'] = "GET";
      $this->setUrl($url);
      $this->merge($options);
      
      $this->send();

      return $this;
    }
  
    function post ($url, $body = null, $opts = []) {
      $this->options['method'] = "POST";
      $this->setUrl($url);
      $this->options['body'] = $body;
      $this->merge($opts);
      
      $this->send();

      return $this;
    }
  
    function send () {
      $this->createCurl();

      if (Fetch::$multi_handle) {
        Fetch::$multi_handle->add($this);
        return;
      }

      $this->sent = true;
      $result = curl_exec($this->ch);
      $this->setResponse($result);
      $this->close();
    }

    function setResponse ($result) {
      $res = (object)curl_getinfo($this->ch);
      $this->response = $res;
      $this->response->raw = $result;
      $data = $result;
      try {
        $data = json_decode($result);
      } catch (Exception $e) {}

      $this->response->data = $data;
      $this->data = $data;

      $this->error = $this->get_error();
    }
  
    function close () {
      curl_close($this->ch);
    }
  
    function get_error () {
      if (curl_error($this->ch)) {
        return curl_error($this->ch);
      }
      return false;
    }
  }

  function fetch ($opts = []) {
    return new Fetch($opts);
  }
} else {
  error_log('Tried including helper "fetch" but the class already exists');
}
?>