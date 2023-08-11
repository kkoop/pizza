<?php
namespace Pizza\Controller;
use Pizza\Model;

class OrderController extends  Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title'  => "Meine Bestellungen",
                          'orders' => Model\Order::getMine()]);
  }

  public function editAction()
  {
    $now = new \DateTime("now");
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
      if ($order->getDay()->time < $now && $day->organizer != $_SESSION['user']->id) {  // Organisator darf nachträglich ändern
        $this->view->setError("Abgelaufene Bestellung kann nicht geändert werden");
        return;
      }
      if ($orderUser->id != $_SESSION['user']->id) {
        // betroffenen Benutzer über die Änderung benachrichtigen
        $url = \Pizza\Library\Mailer::getServerUrl();
        \Pizza\Library\Mailer::mail($orderUser->login, 
          "Änderung an deiner Bestellung",
          sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s wurde durch den Organisator %s gelöscht.\r\n",
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
        if ($orderUser->id != $_SESSION['user']->id && (!K_ORGANIZER_CAN_EDIT || $day->organizer != $_SESSION['user']->id)) {
          $this->view->setError("Bestellung von anderem Benutzer");
          return;
        }
        if ($order->getDay()->time < $now && $day->organizer != $_SESSION['user']->id) {  // Organisator darf nachträglich ändern
          $this->view->setError("Abgelaufene Bestellung kann nicht geändert werden");
          return;
        }
        $before = sprintf("%s (%s) %.2f €", $order->product, $order->comment, $order->price);
        $order->product = $_POST['product'];
        $order->comment = $_POST['comment'];
        $order->price   = $_POST['price'];
        $order->update();
        $after = sprintf("%s (%s) %.2f €", $order->product, $order->comment, $order->price);
        if ($orderUser->id != $_SESSION['user']->id) {
          // betroffenen Benutzer über die Änderung benachrichtigen
          $url = \Pizza\Library\Mailer::getServerUrl();
          \Pizza\Library\Mailer::mail($orderUser->login, 
            "Änderung an deiner Bestellung",
            sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s wurde durch den Organisator %s bearbeitet.\r\n".
                    "Vorher: $before\r\nJetzt: $after\r\n",
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
        if ($day->time < $now) {
          $this->view->setError("Zu abgelaufener Bestellung kann nichts hinzugefügt werden");
          return;
        }
        if (K_ORGANIZER_CAN_ORDER && $day->organizer == $_SESSION['user']->id)
          $user = $_POST['user'];
        else
          $user = $_SESSION['user']->id;
        Model\Order::create($day->id, $_POST['product'], $_POST['comment'], $_POST['price'], $user);
        if ($user != $_SESSION['user']->id) {
          // Bestellung für anderen Benutzer wurde angelegt, betroffenen Benutzer informieren
          $url = \Pizza\Library\Mailer::getServerUrl();
          $after = sprintf("%s (%s) %.2f €", $_POST['product'], $_POST['comment'], $_POST['price']);
          \Pizza\Library\Mailer::mail(Model\User::read($user)->login, 
            "Bestellung in deinem Namen",
            sprintf("Hallo,\r\n\r\nder Organisator %s hat eine Bestellung in deinem Namen angelegt:\r\n%s.\r\n".
                    "Diese kannst du unter %s einsehen.\r\n",
                    $_SESSION['user']->name,
                    $after,
                    "$url/orderday/view/?id=".$day->id));
        }
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
      if ($order->user != $_SESSION['user']->id && $day->organizer != $_SESSION['user']->id) {
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
      $this->view->setVars(['title'             => "Neue Bestellung", 
                            'orderday'          => $day,
                            'showUserSelection' => K_ORGANIZER_CAN_ORDER && $day->organizer == $_SESSION['user']->id,
                            'users'             => Model\User::readAll(),
                            'ownFavourites'     => Model\Recommendation::getOwnFavourites($day),
                            'allFavourites'     => Model\Recommendation::getAllFavourites($day)]);
    }
    if ($menu = Model\Menu::read($day->url)) {
      $this->view->setVars(['menuitems' => $menu->getItems()]);
    }
  }
}

