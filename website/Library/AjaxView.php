<?php
namespace Pizza\Library;

/**
* @brief Gemeinsame Klasse für Ajax-Views
* 
* Alle Ajax-Ausgaben sind die in JSON verpackten View-Variablen.
*/
class AjaxView extends View
{
  public function __construct()
  {
    parent::__construct();
  }

  /**
  * @brief Gibt die Variablen als JSON aus
  * 
  * @return void
  */
  public function render($controllerName, $actionName)
  {
    header("content-type: application/json;charset=utf-8");
    if ($this->errorMsg) {
      echo(json_encode(array("result"=>"error", "error"=>$this->errorMsg)));
    } else {
      echo(json_encode(array_merge(array("result"=>"success"), $this->vars)));
    }
  }
}
