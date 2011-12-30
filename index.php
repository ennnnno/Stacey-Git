<?php

if(phpversion() < 5) {
  
  die('<h3>Stacey requires PHP/5.0 or higher.<br>You are currently running PHP/'.phpversion().'.</h3><p>You should contact your host to see if they can upgrade your version of PHP.</p>');

} else {

  require_once './app/helpers.inc.php';
  foreach(Helpers::rglob('./app/**.inc.php') as $include) include_once $include;

  new Stacey($_GET);
  
}

?>