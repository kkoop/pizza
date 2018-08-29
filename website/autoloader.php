<?php
// Autoload für Controller-Klassen registrieren
spl_autoload_register(function($className) {
  if (substr($className, 0, 6) != 'Pizza\\')
    return;
  $fileName = __DIR__.'/'.str_replace('\\', '//', substr($className, 6)).'.php';
  if (file_exists($fileName)) {
    include($fileName);
  }
});
