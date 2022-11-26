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
    if (!($day = Model\Orderday::read($_REQUEST['id']))) {
      $this->view->setError("Fehler beim Lesen des Bestelltages");
      return;
    }
    $this->view->setVars(['orderday'          => $day,
                          'orders'            => $day->getOrders(),
                          'showBtnAdd'        => $day->time > time(),
                          'showBtnEdit'       => $day->organizer == $_SESSION['user']->id,
                          'showBtnOrderReady' => $day->time < time() && $day->time+86400 > time() 
                                             && !$day->mailready && $day->organizer==$_SESSION['user']->id]);
  }
  
  public function newAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
    if (isset($_POST['time'])) {
      if (($day = Model\Orderday::create($_POST['time'], $_POST['deliveryService'], $_POST['deliveryServiceUrl'])) == null) {
        $this->view->setError("Fehler beim Anlegen des Bestelltages");
        return;
      }
      // E-Mail an alle, die eine haben möchten, aber nicht an uns selbst
      $url = \Pizza\Library\Mailer::getServerUrl();
      foreach (Model\User::readAll() as $user) {
        if ($user->notify_neworder && $user->id != $_SESSION['user']->id) {
          \Pizza\Library\Mailer::mail($user->login, 
            "Neue gemeinsame Bestellung", 
            sprintf("Hallo,\r\n\r\neine neue gemeinsame Bestellung wurde angelegt.\r\n".
              "Unter %s kannst du deine Bestellung hinzufügen.\r\n",
              "$url/orderday/view/?id=".$day->id),
            strtotime($_POST['time']),
            true);
        }
      }
      // Speisekarte scrapen
      if ($scraper = \Pizza\Library\Scraper::create($_POST['deliveryServiceUrl'])) {
        $scraper->scrape();
      }
      header("Location: ".K_BASE_URL."/orderday/view/?id={$day->id}");
      exit(0);
    }
  }
  
  public function editAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
    if (!($day = Model\Orderday::read($_REQUEST['id']))) {
      $this->view->setError("Fehler beim Lesen des Bestelltages");
      return;
    }
    if ($day->organizer != $_SESSION['user']->id) {
      $this->view->setError("Nur der Ersteller kann bearbeiten.");
      return;
    }
    if (isset($_POST['time'])) {
      $day->time = $_POST['time'];
      $day->deliveryservice = $_POST['deliveryService'];
      $day->url = $_POST['deliveryServiceUrl'];
      $newOrganizer = $day->organizer != $_POST['organizer'];
      $day->organizer = $_POST['organizer'];
      if (!$day->write()) {
        $this->view->setError("Fehler beim Schreiben der Änderungen");
        exit(1);
      }
      if ($newOrganizer) {
        $url = \Pizza\Library\Mailer::getServerUrl();
        \Pizza\Library\Mailer::mail($day->getOrganizer()->login, 
          "Gemeinsame Bestellung übertragen", 
          sprintf("Hallo,\r\n\r\ndu wurdest als Organisator für eine gemeinsame Bestellung eingetragen.\r\n".
            "Unter %s kannst du die Bestellung ansehen und bearbeiten.\r\n",
            "$url/orderday/view/?id=".$day->id),
          strtotime($_POST['time']));
      }
      header("Location: ".K_BASE_URL."/orderday/view/?id={$day->id}");
      exit(0);
    }
    $this->view->setVars(["day" => $day, "users" => Model\User::readAll()]);
  }

  public function deleteAjax()
  {
    if (!($day = Model\Orderday::read($_REQUEST['id']))) {
      $this->view->setError("Fehler beim Lesen des Bestelltages");
      return;
    }
    if ($day->organizer != $_SESSION['user']->id) {
      $this->view->setError("Nur der Ersteller kann löschen.");
      return;
    }
    // Besteller informieren
    $users = [];  // jeder soll nur eine Mail bekommen, auch bei mehreren Bestellungen
    foreach ($day->getOrders() as $order) {
      $user = $order->getUser();
      if ($user->id != $_SESSION['user']->id)
        $users[$user->id] = $user;
    }
    foreach ($users as $user) {
      \Pizza\Library\Mailer::mail($user->login, "Bestellung gelöscht", 
        "Hallo,\r\n\r\ndie gemeinsame Bestellung wurde durch den Organisator gelöscht.\r\n",
        strtotime("+1 hour"));
    }
    $day->delete();
  }

  public function readyMailAjax()
  {
    $day = Model\Orderday::read($_REQUEST['id']);
    if (!$day) {
      $this->view->setError("Bestelltag nicht gefunden");
      return;
    }
    if ($day->organizer != $_SESSION['user']->id) {
      $this->view->setError("Benutzer ist nicht Organisator");
      return;
    }
    $day->mailReadySent();
    // Mail an alle anderen Besteller, die eine Mail haben möchten
    $users = [];  // jeder soll nur eine Mail bekommen, auch bei mehreren Bestellungen
    foreach ($day->getOrders() as $order) {
      $user = $order->getUser();
      if ($user->notify_orderready && $user->id != $_SESSION['user']->id)
        $users[$user->id] = $user;
    }
    $url = \Pizza\Library\Mailer::getServerUrl();
    foreach ($users as $user) {
      \Pizza\Library\Mailer::mail($user->login, "Bestellung ist da", 
        sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s ist angekommen.\r\n",
              "$url/orderday/view/?id=".$day->id),
        strtotime("+1 hour"));
    }
  }
}
