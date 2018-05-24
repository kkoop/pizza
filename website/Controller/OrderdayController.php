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
            sprintf("Hallo,\n\neine neue gemeinsame Bestellung wurde angelegt.\n".
              "Unter %s%d kannst du deine Bestellung hinzufügen.\n\n".
              "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\n",
              "$url/orderday/view/?id=",
              $day->id));
        }
      }
    } else {
      if (!($day = Model\Orderday::read($_REQUEST['id']))) {
        $this->view->setError("Fehler beim Lesen des Bestelltages");
        return;
      }
    }
    $this->view->setVars(['orderday' => $day,
                          'orders'   => $day->getOrders()]);
  }
  
  public function newAction()
  {
    $this->view->setVars(['title' => "Bestellungen"]);
  }
}
