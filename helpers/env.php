<?

/* 
Site is considered in development when using a .local domain
OR when ?dev=true parameter is used in the url
*/

if (!function_exists('is_dev')) {
  function is_dev() {
    return strpos($_SERVER['SERVER_NAME'], '.local') !== 0 || get($_GET, 'dev', false);
  }
} else {
  error_log('Tried including helper "is_dev" but the function already exists');
}

?>