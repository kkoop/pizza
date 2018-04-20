<?php
namespace Pizza\Library;

class View
{
  protected $vars;
  protected $errorMsg;

  public function __construct()
  {
    $this->vars = array();
  }

  public function setError($errorMsg)
  {
    $this->errorMsg = $errorMsg;
  }

  public function setVars(array $vars)
  {
    $this->vars = array_merge($this->vars, $vars);
  }

  public function render($controllerName, $actionName)
  {
    if ($this->errorMsg) {
      $fileName = __DIR__.'/../View/error.phtml';
      $errorMsg = $this->errorMsg;
    } else {
      $fileName = __DIR__.'/../View/'. $controllerName . '/' . $actionName . '.phtml';
    }
    if (!file_exists($fileName)) {
      printf("view %s not found\n", $fileName);
      throw new NotFoundException();
    }
    // zur einfachen Handhabung View-Variablen als globale Variablen mit dem Namen des Keys erzeugen
    foreach($this->vars as $key => $val)
      $$key = $val;
    $menu = $menu ?? '';
    $subMenu = $subMenu ?? '';
    $title = $title ?? '';

    $viewFileName = $fileName;
    // Grundgerüst der Seite ausgeben, die wiederum die tatsächliche Seite inkludiert
    if ($this->isLoggedIn()) {
      include(__DIR__.'/../View/frame.phtml');
    } else {
      include(__DIR__.'/../View/frame_nologin.phtml');
    }
  }

  protected function isLoggedIn()
  {
    return !empty($_SESSION['user']->id);
  }
}
