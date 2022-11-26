<?php
namespace Pizza\Controller;
use Pizza\Model;

class UploadController extends  Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => 'Dateien']);
    $this->view->setVars(['files' => Model\File::getAll()]);
  }
  
  public function fileAction()
  {
    if ($file = Model\File::read($_REQUEST['id'])) {
      $blob = $file->getBlob($mime);
      //header("Content-Disposition: attachment");
      header("Content-Type: $mime");
      header("Content-Length: ".strlen($blob));
      echo($blob);
      exit(0);
    }
    $this->view->setError("Datei nicht gefunden");
    http_response_code(404);
  }
  
  public function newAction()
  {
    if (isset($_POST['title'])) {
      if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
        $this->view->setError("Fehler beim Hochladen: ".$_FILES['file']['error']);
        return;
      }
      if (Model\File::create($_POST['title'], $_FILES['file']['tmp_name'], $_FILES['file']['type'], $_POST['expiry'])) {
        header('Location:'.K_BASE_URL.'/upload');
        // E-Mail an alle, die eine haben möchten, aber nicht an uns selbst
        $url = \Pizza\Library\Mailer::getServerUrl();
        foreach (Model\User::readAll() as $user) {
          if ($user->notify_newfile && $user->id != $_SESSION['user']->id) {
            \Pizza\Library\Mailer::mail($user->login, 
              "Neue Datei hochgeladen", 
              sprintf("Hallo,\r\n\r\n%s hat eine neue Datei hochgeladen.\r\n".
                "Unter %s kannst du diese ansehen.\r\n",
                $_SESSION['user']->name,
                "$url/upload"),
              strtotime($_POST['time']));
          }
        }
        exit(0);
      } else {
        $this->view->setError("Fehler beim Speichern der Datei");
      }
    } else {
      $this->view->setVars(['title' => 'Neue Datei']);
    }
  }
  
  public function deleteAction()
  {
    if ($file = Model\File::read($_REQUEST['id'])) {
      if ($file->user != $_SESSION['user']->id) {
        $this->view->setError("Nur eigene Dateien können gelöscht werden.");
        return;
      }
      if (!$file->delete()) {
        $this->view->setError("Fehler beim Löschen der Datei");
        return;
      }
    }
    header('Location:'.K_BASE_URL.'/upload');
    exit(0);
  }
}
