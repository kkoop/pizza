<?php
namespace Pizza\Controller;

class Controller 
{
  protected $view;
  protected $needLogin=true;

  public function __construct()
  {
    if ($this->needLogin && !$this->isLoggedIn()) {
      throw new \Pizza\Library\NeedsLoginException();
    }
  }

  public function setView($view)
  {
    $this->view = $view;
  }

  protected function isLoggedIn()
  {
    return !empty($_SESSION['user']->id);
  }
}
