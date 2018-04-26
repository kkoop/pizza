<?php
namespace Pizza\Controller;
use Pizza\Model;

class SettingsController extends Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Einstellungen"]);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $_SESSION['user']->notify_neworder = $_POST['notify_neworder'] ?? false;
      if ($_SESSION['user']->writeSettings()) {
        $this->view->setVars(['successMessage' => "Einstellungen gespeichert"]);
      } else {
        $this->view->setError("Fehler beim Speichern");
      }
    }
    $this->view->setVars(['user' => $_SESSION['user']]);
  }
}

