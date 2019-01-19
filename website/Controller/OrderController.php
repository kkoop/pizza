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
    if (isset($_POST['delete'])) {
      // Eintrag löschen
      if (!($order = Model\Order::read($_POST['order']))) {
        $this->view->setError("Fehler beim Lesen der Bestellung");
        return;
      }
      $day = $order->getDay();
      $orderUser = $order->getUser();
      if ($orderUser->id != $_SESSION['user']->id && $day->organizer != $_SESSION['user']->id) {
        $this->view->setError("Bestellung von anderem Benutzer");
        return;
      }
      if ($order->getDay()->time < time() && $day->organizer != $_SESSION['user']->id) {  // Organisator darf nachträglich ändern
        $this->view->setError("Abgelaufene Bestellung kann nicht geändert werden");
        return;
      }
      if ($orderUser->id != $_SESSION['user']->id) {
        // betroffenen Benutzer über die Änderung benachrichtigen
        $url = "http://".($_SERVER['SERVER_NAME'] ?? "").K_BASE_URL;
        \Pizza\Library\Mailer::mail($orderUser->login, 
          "Änderung an deiner Bestellung",
          sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s wurde durch den Organisator %s gelöscht.\r\n".
                  "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\r\n",
                  "$url/orderday/view/?id=".$day->id,
                  $_SESSION['user']->name));
      }
      $order->delete();
      header("Location: ".K_BASE_URL."/orderday/view/?id={$order->day}");
      exit(0);
    } elseif (isset($_POST['day'])) {
      if (!empty($_POST['order'])) {
        // Eintrag bearbeitet
        if (!($order = Model\Order::read($_POST['order']))) {
          $this->view->setError("Fehler beim Lesen der Bestellung");
          return;
        }
        $day = $order->getDay();
        $orderUser = $order->getUser();
        if ($orderUser->id != $_SESSION['user']->id && $day->organizer != $_SESSION['user']->id) {
          $this->view->setError("Bestellung von anderem Benutzer");
          return;
        }
        if ($order->getDay()->time < time() && $day->organizer != $_SESSION['user']->id) {  // Organisator darf nachträglich ändern
          $this->view->setError("Abgelaufene Bestellung kann nicht geändert werden");
          return;
        }
        $order->product = $_POST['product'];
        $order->comment = $_POST['comment'];
        $order->price   = $_POST['price'];
        $order->update();
        if ($orderUser->id != $_SESSION['user']->id) {
          // betroffenen Benutzer über die Änderung benachrichtigen
          $url = "http://".($_SERVER['SERVER_NAME'] ?? "").K_BASE_URL;
          \Pizza\Library\Mailer::mail($orderUser->login, 
            "Änderung an deiner Bestellung",
            sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s wurde durch den Organisator %s bearbeitet.\r\n".
                    "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\r\n",
                    "$url/orderday/view/?id=".$day->id,
                    $_SESSION['user']->name));
        }
        header("Location: ".K_BASE_URL."/orderday/view/?id={$order->day}");
        exit(0);
      } else {
        // neuer Eintrag
        if (!($day = Model\Orderday::read($_POST['day']))) {
          $this->view->setError("Fehler beim Lesen des Bestelltags");
          return;
        }
        if ($day->time < time()) {
          $this->view->setError("Zu abgelaufener Bestellung kann nichts hinzugefügt werden");
          return;
        }
        Model\Order::create($day->id, $_POST['product'], $_POST['comment'], $_POST['price']);
        header("Location: ".K_BASE_URL."/orderday/view/?id={$day->id}");
        exit(0);
      }
    }
    if (!empty($_REQUEST['id'])) {
      if (!($order = Model\Order::read($_REQUEST['id']))) {
        $this->view->setError("Fehler beim Lesen der Bestellung");
        return;
      }
      $day = $order->getDay();
      if ($order->getUser()->id != $_SESSION['user']->id && $day->organizer != $_SESSION['user']->id) {
        $this->view->setError("Bestellung von anderem Benutzer");
        return;
      }
      $this->view->setVars(['title'    => "Bestellung bearbeiten", 
                            'order'    => $order, 
                            'orderday' => $day]);
    } else {
      if (!($day = Model\Orderday::read($_REQUEST['day']))) {
        $this->view->setError("Fehler beim Lesen des Bestelltags");
        return;
      }
      $this->view->setVars(['title'    => "Neue Bestellung", 
                            'orderday' => $day]);
    }
    if ($menu = Model\Menu::read($day->url)) {
      $this->view->setVars(['menuitems' => $menu->getItems()]);
    }
  }
}

