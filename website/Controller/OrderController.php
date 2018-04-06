<?php
namespace Pizza\Controller;
use Pizza\Model;

class OrderController extends  Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
    $this->view->setVars(['openOrderDays' => Model\Orderday::readAll(time()),
                          'pastOrderDays' => Model\Orderday::readAll(strtotime("-1 month"), time())]);
  }
}
