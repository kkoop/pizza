<?php
namespace Pizza\Controller;
use Pizza\Model;

class SettingsController extends Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Einstellungen"]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      if (!empty($_POST['paypal'])) {
        if (($pos = stripos($_POST['paypal'], "paypal.me/")) !== false)
          $_POST['paypal'] = substr($_POST['paypal'], $pos+10);
      }
      $_SESSION['user']->notify_neworder    = $_POST['notify_neworder']   ?? false;
      $_SESSION['user']->notify_orderdue    = $_POST['notify_orderdue']   ?? false;
      $_SESSION['user']->notify_orderready  = $_POST['notify_orderready'] ?? false;
      $_SESSION['user']->notify_newfile     = $_POST['notify_newfile']    ?? false;
      $_SESSION['user']->paypal             = $_POST['paypal']            ?? null;
      if ($_SESSION['user']->writeSettings()) {
        $this->view->setVars(['successMessage' => "Einstellungen gespeichert"]);
      } else {
        $this->view->setError("Fehler beim Speichern");
      }
    }
    $this->view->setVars(['user' => $_SESSION['user']]);
  }
}

