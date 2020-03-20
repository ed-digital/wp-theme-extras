<?

/* 
Site is considered in development when using a .local domain
OR when ?dev=true parameter is used in the url
*/
function is_dev() {
  return strpos($_SERVER['SERVER_NAME'], '.local') !== 0 || get($_GET, 'dev', false);
}

?>