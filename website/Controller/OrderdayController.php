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
      $url = "/";
      if (isset($_SERVER['SERVER_NAME'])) {
        $url = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']);
      }
      foreach (Model\User::readAll() as $user) {
        if ($user->notify_neworder && $user->id != $_SESSION['user']->id) {
          \Pizza\Library\Mailer::mail($user->login, 
            "Neue gemeinsame Bestellung", 
            sprintf("Hallo,\r\n\r\neine neue gemeinsame Bestellung wurde angelegt.\r\n".
              "Unter %s kannst du deine Bestellung hinzufügen.\r\n".
              "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\r\n",
              "$url/orderday/view/?id=".$day->id),
            strtotime($_POST['time']));
        }
      }
      header("Location: ".K_BASE_URL."/orderday/view/?id={$day->id}");
      exit(0);
    }
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
    $url = "/";
    if (isset($_SERVER['SERVER_NAME'])) {
      $url = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']);
    }
    foreach ($users as $user) {
      \Pizza\Library\Mailer::mail($user->login, "Bestellung ist da", 
        sprintf("Hallo,\r\n\r\ndeine Bestellung zu %s ist angekommen.\r\n".
                "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\r\n",
              "$url/orderday/view/?id=".$day->id),
        strtotime("+1 hour"));
    }
  }
}
