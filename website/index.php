<?php 
/** @file
 * @brief Einstiegspunkt für alle Webseiten
 */
namespace Pizza;
$baseUrl = "/~koop/pizza";

// Autoload für Controller-Klassen registrieren
spl_autoload_register(function($className) {
  if (substr($className, 0, 6) != 'Pizza\\')
    return;
  $fileName = __DIR__.'/'.str_replace('\\', '//', substr($className, 6)).'.php';
  if (file_exists($fileName)) {
    include($fileName);
  }
});

//set_exception_handler('Log::exceptionHandler');
session_start();
//bindtextdomain("messages", "./l10n");
//textdomain("messages");

// falls nicht angemeldet: prüfen, ob rememberme-Cookie gesetzt ist
if (!isset($_SESSION['user']) && isset($_COOKIE['rememberme'])) {
  if ($user = Model\User::fromRememberToken($_COOKIE['rememberme'])) {
    $_SESSION['user'] = $user;
  }
}

// ursprüngliche URL, die durch mod_rewrite übergeben wurde
$url      = $_GET['_url'] ?? '';
$urlParts = explode('/', $url);
// Name der Controller-Klasse ist erster Teil der URL
$controllerName      = !empty($urlParts[0]) ? $urlParts[0] : 'index';
$controllerClassName = '\\Pizza\\Controller\\'.ucfirst($controllerName).'Controller';
// Name der Funktion (action) ist zweiter Teil der URL
$actionName       = !empty($urlParts[1]) ? $urlParts[1] : 'index';
$actionMethodName = $actionName;
// optionaler dritter Teil gibt Art des Aufrufs an (z.B. ajax)
$viewType = $_GET['_type'] ?? "action";
$actionMethodName .= ucfirst($viewType);

try {
  if (!class_exists($controllerClassName)) {
    printf("class %s not found\n", $controllerClassName);
    throw new \Pizza\Library\NotFoundException();
  }
  // Controller instanziieren
  $controller = new $controllerClassName();
  if (!$controller instanceof \Pizza\Controller\Controller || !method_exists($controller, $actionMethodName)) {
    printf("action %s not found\n", $actionMethodName);
    throw new \Pizza\Library\NotFoundException();
  }
  // View erzeugen
  switch ($viewType) {
    case "ajax":
      $view = new \Pizza\Library\AjaxView();
      break;
    default:
      $view = new \Pizza\Library\View();
      break;
  }
  $controller->setView($view);
  // action aufrufen und View ausgeben
  $controller->$actionMethodName();
  $view->render($controllerName, $actionName);
} catch (\Pizza\Library\NotFoundException $e) {
  http_response_code(404);
  include('error_pages/404.php');
} catch (\Pizza\Library\NeedsLoginException $e) {
  $_SESSION['redirect'] = "/$url";
  header('Location: /index/login');
} catch (\Exception $e) {
  http_response_code(500);
  echo 'Exception: '.$e->getMessage().' '.$e->getTraceAsString();
}
