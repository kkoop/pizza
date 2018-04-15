<?php
namespace Pizza\Controller;
use Pizza\Model;

class OrderController extends  Controller
{
  public function indexAction()
  { 
    
  }
  
  public function editAction()
  {
    if (isset($_POST['day'])) {
      if (!empty($_POST['order'])) {
        // Eintrag bearbeitet
        if (!($order = Model\Order::read($_POST['order']))) {
          $this->view->setError("Fehler beim Lesen der Bestellung");
          return;
        }
        if ($order->getUser()->id != $_SESSION['user']->id) {
          $this->view->setError("Bestellung von anderem Benutzer");
          return;
        }
        $order->product = $_POST['product'];
        $order->comment = $_POST['comment'];
        $order->price   = $_POST['price'];
        $order->update();
        header("Location: {$GLOBALS['baseUrl']}/orderday/view/?id={$order->day}");
        exit(0);
      } else {
        // neuer Eintrag
        if (!($day = Model\Orderday::read($_POST['day']))) {
          $this->view->setError("Fehler beim Lesen des Bestelltags");
          return;
        }
        Model\Order::create($day->id, $_POST['product'], $_POST['comment'], $_POST['price']);
        header("Location: {$GLOBALS['baseUrl']}/orderday/view/?id={$day->id}");
        exit(0);
      }
    }
    if (!empty($_REQUEST['id'])) {
      if (!($order = Model\Order::read($_REQUEST['id']))) {
        $this->view->setError("Fehler beim Lesen der Bestellung");
        return;
      }
      $this->view->setVars(['title' => "Bestellung bearbeiten", 'order' => $order, 'orderday' => $order->getDay()]);
    } else {
      if (!($day = Model\Orderday::read($_REQUEST['day']))) {
        $this->view->setError("Fehler beim Lesen des Bestelltags");
        return;
      }
      $this->view->setVars(['title' => "Neue Bestellung", 'orderday' => $day]);
    }
  }
}
