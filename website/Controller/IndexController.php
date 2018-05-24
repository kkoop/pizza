<?php
namespace Pizza\Controller;
use Pizza\Model;

class IndexController extends Controller
{
  protected $needLogin=false;

  public function indexAction()
  {
    if ($this->isLoggedIn()) {
      header('Location:'.K_BASE_URL.'/orderday');
    } else {
      header('Location:'.K_BASE_URL.'/index/login');
    }
    exit(0);
  }

  public function loginAction()
  {
    if ($this->isLoggedIn()) {
      header('Location:'.K_BASE_URL.'/orderday');
      exit(0);
    }
    $this->view->setVars(['title' => "Login"]);

    if (isset($_POST['account'])) {
      usleep(400000);
      if ($user = Model\User::login($_POST['account'], $_POST['password'], $_SERVER['REMOTE_ADDR'])) {
        // Login hat funktioniert, zur Startseite weiterleiten
        $_SESSION['user'] = $user;
        if (isset($_POST['rememberme'])) {
          $token = $user->createRememberToken();
          setcookie("rememberme", $token, time()+60*60*24*120, "", "", false, true);
        }
        if (!empty($_SESSION['redirect'])) {
          $redirect = $_SESSION['redirect'];
          unset($_SESSION['redirect']);
        } else {
          $redirect = "/";
        }
        header("Location:".K_BASE_URL.$redirect);
        exit(0);
      } else {
        // falscher Benutzername/Passwort
        $this->view->setVars(['errorMessage' => _("Fehler beim Anmelden, ungültiger Benutzer oder falsches Passwort.").
          '<br><a href="'.K_BASE_URL.'/index/resetPassword">'._("Passwort vergessen").'?</a>']);
      }
    }
    if (isset($_POST['regEmail'])) {
      // Registrier-Formular wurde gesendet
      if (Model\User::loginExists($_POST['regEmail'])) {
        $this->view->setVars([
          'showRegForm'  => true,
          'errorMessage' => _("Diese E-Mail-Adresse ist bereits registriert.")]);
      } else {
        if (Model\User::newUser($_POST['regEmail'], $_POST['regName'], "RW")) {
          $this->view->setVars([
            'successMessage' => _("Registrierung wurde entgegengenommen. Sie erhalten in Kürze eine E-Mail mit allen weiteren Informationen.")]);
        } else {
          $this->view->setVars([
            'showRegForm'  => true,
            'errorMessage' => _("Fehler beim Speichern der Daten.")]);
        }
      }
    }
  }

  public function logoutAction()
  {
    $this->view->setVars(['title' => "Logout"]);
    if (isset($_SESSION['user'])) {
      $_SESSION['user']->logout();
    }
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    setcookie("rememberme", '', time() - 42000);
    session_destroy();
    $_SESSION = array();
  }

  public function privacyAction()
  {
    $this->view->setVars(['title' => _("Datenschutz")]);
  }
  
  public function imprintAction()
  {
    $this->view->setVars(['title' => _("Impressum")]);
  }
  
  public function resetPasswordAction()
  {
    $this->view->setVars(['title' => _("Passwortreset")]);
    if (isset($_POST['password'])) {
      // Schritt 4: neues Passwort wurde eingegeben
      if ($_POST['password'] != $_POST['password2']) {
        $this->view->setVars(["step" => 3,
          "token" => $_REQUEST['token'],
          "errorMessage" => _("Passwörter stimmen nicht überein.")]);
      } else if (strlen($_POST['password']) < K_MIN_PASSWORD_LEN) {
        $this->view->setVars(["step" => 3,
          "token" => $_REQUEST['token'],
          "errorMessage" => sprintf(_("Passwort ist zu kurz. Geben Sie mindestens %d Zeichen ein."), K_MIN_PASSWORD_LEN)]);
      } else {
        if (Model\User::passwordReset($_REQUEST['token'], $_POST['password'])) {
          $this->view->setVars(["step" => 4]);
        } else {
          $this->view->setError(_("Beim Ändern des Passworts ist ein Fehler aufgetreten."));
        }
      }
    } elseif (isset($_REQUEST['token'])) {
      // Schritt 3: E-Mail-Link wurde aufgerufen
      if (Model\User::validResetToken($_REQUEST['token'])) {
        $this->view->setVars(["step" => 3, "token" => $_REQUEST['token']]);
      } else {
        $this->view->setError(_("Ungültiger Link."));
      }
    } elseif (isset($_POST['email'])) {
      // Schritt 2: E-Mail wurde angefordert
      $subject = sprintf(_("Passwort für %s zurücksetzen"), K_PRODUCT_NAME);
      $text = sprintf(_("Guten Tag,\n\nSie haben vor Kurzem ein neues Passwort beantragt. Falls diese Anfrage nicht von Ihnen kam, ignorieren Sie diese E-Mail.\n".
        "Um Ihr Passwort zurückzusetzen, folgen Sie diesem Link: \n%s\n\n".
        "Dies ist eine automatisch generierte E-Mail. Antworten werden nicht zugestellt.\n"),
        "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/index/resetPassword?token=%s");
      Model\User::passwordResetEmail($_POST['email'], $_SERVER['REMOTE_ADDR'], $subject, $text);
      // immer positive Rückmeldung, egal ob E-Mail wirklich geschickt wird, damit niemand E-Mails auf 
      // existierende Accounts abfragen kann
      sleep(1);
      $this->view->setVars(["step" => 2]);
    } else {
      // Schritt 1: Link "Passwort vergessen" wurde aufgerufen
      $this->view->setVars(["step" => 1]);
    }
  }
  
  public function activateAction()
  {
    $this->view->setVars(['title' => "Benutzer aktivieren"]);
    if (!isset($_REQUEST['token']) || ($user = Model\User::fromToken($_REQUEST['token'])) == NULL) {
      $this->view->setError(_("Ungültiger Link."));
      return;
    }
    $this->view->setVars(["token" => $_REQUEST['token'], "login" => $user->login]);
    if (!empty($_REQUEST['password'])) {
      // Schritt 2: Passwort wurde eingegeben, Zugang kann aktiviert werden
      if ($_POST['password'] != $_POST['password2']) {
        $this->view->setVars(["errorMessage" => _("Passwörter stimmen nicht überein.")]);
      } else if (strlen($_POST['password']) < K_MIN_PASSWORD_LEN) {
        $this->view->setVars(["errorMessage" => _("Passwort ist zu kurz. Geben Sie mindestens %d Zeichen ein.")]);
      } else if ($user->activate($_POST['password'])) {
        $this->view->setVars(["successMessage" => _("Ihr Zugang wurde aktiviert. Sie werden nun auf die Startseite weitergeleitet.")]);
        return;
      } else {
        $this->view->setVars(["errorMessage" => _("Fehler beim Schreiben in die Datenbank.")]);
      }
    } else {
      // Schritt 1: Seite wird über Link aus E-Mail aufgerufen
    }
  }
}
