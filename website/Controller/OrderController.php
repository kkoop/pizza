<?php
namespace Pizza\Controller;
use Pizza\Model;

class OrderController extends  Controller
{
  public function indexAction()
  { 
    
  }
  
  public function newAction()
  {
    $this->view->setVars(['title' => "Neue Bestellung"]);
    if (!($day = Model\Orderday::read($_REQUEST['day']))) {
      $this->view->setError("Fehler beim Lesen des Bestelltags");
      return;
    }
  }
}
