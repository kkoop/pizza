<?php
namespace Pizza\Controller;
use Pizza\Model;

class OrderdayController extends Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
    $this->view->setVars(['openOrderDays' => Model\Orderday::readAll(time()),
                          'pastOrderDays' => Model\Orderday::readAll(strtotime("-1 month"), time())]);
  }

  public function viewAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
    if (isset($_POST['time'])) {
      if (($day = Model\Orderday::create($_POST['time'], $_POST['deliveryService'])) == null) {
        $this->view->setError("Fehler beim Anlegen des Bestelltages");
        return;
      }
    } else {
      if (!($day = Model\Orderday::read($_REQUEST['id']))) {
        $this->view->setError("Fehler beim Lesen des Bestelltages");
        return;
      }
    }
    $this->view->setVars(['orderday' => $day,
                          'ownOrder' => $day->getMyOrder(),
                          'orders'   => $day->getOrders()]);
  }
  
  public function newAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
  }
}
