<?php
namespace Pizza\Model;

/**
 * @brief Ein Benutzer, der sich anmelden kann
 * 
 */
class User 
{
  public $id;
  public $login;
  public $name;
  public $rights;
  public $disabled;
  // Einstellungen:
  public $notify_neworder;
  public $notfiy_orderdue;
  public $notfiy_orderready;
  public $notify_newfile;

  public function __construct()
  {
  }

/**
 * @brief Funktion, die beim Deserialisieren aufgerufen wird
 *
 * Da der eingeloggte User in $_SESSION gespeichert wird, wird diese Funktion automatisch beim Initialisieren
 * der Session aufgerufen. Wir nutzen dies, um globale Einstellungen auf die des User zu setzen.
 */
  public function __wakeup()
  {
  }

/**
 * @brief Führt Login durch
 * 
 * Benutzername und Passwort werden geprüft, bei Erfolg wird eine neue Instanz zurückgegeben, sonst null.
 * @param string $account  Benutzername
 * @param string $password Passwort
 * @param string|null $ip  IP-Adresse, um nach mehreren falschen Eingaben von einer IP eine CAPTCHA anzuzeigen
 * @return User|null
 */
  public static function login($account, $password, $ip=null)
  {
    $stmt = Db::prepare("SELECT user.* ".
      "FROM user ".
      "WHERE login=:login AND token IS NULL");
    $stmt->execute(array(":login"=>$account));
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    if ($user = $stmt->fetch(\PDO::FETCH_CLASS)) {
      if (password_verify($password, $user->password)) {
        // fehlgeschlagene Versuche für die IP zurücksetzen
        /*if ($ip) {
          $stmt = Db::prepare("DELETE FROM user__failedlogin WHERE ip=:ip");
          $stmt->execute(array(":ip"=>$ip));
        }*/
        $user->doLogin();
        return $user;
      }
      // fehlgeschlagene Versuche für diesen Benutzer hochzählen
      /*$stmt = Db::prepare("UPDATE user SET failedlogins=last_insert_id(failedlogins+1) WHERE id=:id");
      $stmt->execute(array(":id"=>$user->id));*/
    }
    // fehlgeschlagene Versuche für diese IP hochzählen
    /*if ($ip) {
      $stmt = Db::prepare("INSERT INTO user__failedlogin (ip) VALUES (:ip) ON DUPLICATE KEY UPDATE failedcount=failedcount+1");
      $stmt->execute(array(":ip"=>$ip));
    }*/
    return null;
  }
  
/**
 * @brief Login über "angemeldet bleiben"-Cookie
 * 
 * @param string $token Im Cookie gespeichertes Token
 * @return User|null
 */
  public static function fromRememberToken($token)
  {
    $stmt = Db::prepare("SELECT user.* ".
      "FROM user__remember ".
      "INNER JOIN user ON user.id=user__remember.user ".
      "WHERE user__remember.token=:token AND disabled=0 AND user.token IS NULL");
    $stmt->execute(array(":token"=>hash("sha256", $token)));
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    if ($user = $stmt->fetch(\PDO::FETCH_CLASS)) {
      $user->doLogin();
    }
    return $user;
  }
  
/**
 * @brief Erzeugt ein Token, das für ein "eingeloggt bleiben"-Cookie verwendet werden kann
 * 
 * Es wird ein Token zurückgegeben, in der Datenbank wird aber der Hash vom Token gespeichert, analog Aktivierungstoken
 * @return string Token als Hex-String
 */
  public function createRememberToken()
  {
    $token = bin2hex(random_bytes(32));
    $hash = hash("sha256", $token);
    $stmt = Db::prepare("INSERT INTO user__remember (token,user) VALUES (:token,:user)");
    if ($stmt->execute(array(":token"=> $hash, ":user"=>$this->id))) {
      return $token;
    }
    return null;
  }
  
  private function doLogin()
  {
    $this->init();
/*    $stmt = Db::prepare("UPDATE user SET lastlogin=NOW(),failedlogins=0 WHERE id=:id");
    $stmt->execute(array(":id"=>$this->id));*/
    Log::info("login", $this);
  }
  
/*  public static function failedCountForIp($ip)
  {
    $stmt = Db::prepare("SELECT COALESCE(SUM(failedcount),0) FROM user__failedlogin WHERE ip=:ip");
    $stmt->execute(array(":ip"=>$ip));
    return $stmt->fetchColumn();
  }*/

  private function init()
  {
    $this->password = "";
  }

/**
 * @brief Meldet den Benutzer ab
 * 
 * @param string|null $token Das remember-me-Token, das, falls nicht null, gelöscht wird
 * @return void
 */
  public function logout($token=null)
  {
    Log::info("logout", $this);
    if ($token!==null) {
      $stmt = Db::prepare("DELETE FROM user__remember WHERE token=:token");
      $stmt->execute(array(":token"=>hash("sha256", $token)));
    }
  }

/**
 * @brief Erzeugt eine Instanz der Klasse anhand der übergebenen Id
 * 
 * @param int $id Datenbank-ID des Geräts
 * @return User|null
 */
  public static function read($id)
  {
    $stmt = Db::prepare("SELECT user.* ".
      "FROM user ".
      "WHERE user.id=:id");
    $stmt->execute(array(":id"=>$id));
    $stmt->setFetchMode(\PDO::FETCH_CLASS, get_class());
    if ($user = $stmt->fetch(\PDO::FETCH_CLASS)) {
      $user->init();
      return $user;
    }
    return null;
  }

/**
 * @brief Benutzer über Token lesen
 * 
 * Nach dem Anlegen eines Benutzers durch entweder den Schul-Admin oder den LD-Admin bekommt dieser eine 
 * Aktivierungs-E-Mail. In dieser ist ein Link mit Aktivierungstoken enthalten. Über dieses Token wird der 
 * Benutzer identifiziert und kann dann sein Passwort erstellen.
 * @param string $token Token
 * @return null
 */
  public static function fromToken($token)
  {
    $stmt = Db::prepare("SELECT user.* ".
      "FROM user ".
      "WHERE user.token=:token");
    $stmt->execute(array(":token"=>hash("sha256", $token)));
    $stmt->setFetchMode(\PDO::FETCH_CLASS, "Pizza\Model\User");
    if ($user = $stmt->fetch(\PDO::FETCH_CLASS)) {
      $user->init();
      return $user;
    }
    return null;
  }

/**
 * @brief Passwort ändern
 * 
 * Prüft zunächst das alte Passwort
 * @param string|null $oldpw Altes Passwort, oder null, falls das alte Passwort nicht geprüft werden soll
 * @param string $newpw Neues Passwort
 * @return bool @retval true Erfolg
 */
  public function changePw($oldpw, $newpw) 
  {
    $stmt = Db::prepare("SELECT password FROM user WHERE id=:id");
    $stmt->execute(array(":id"=>$this->id));
    if ($res = $stmt->fetch()) {
      if ($oldpw===null || password_verify($oldpw, $res['password'])) {
        $stmt = Db::prepare("UPDATE user SET password=:password,token=NULL WHERE id=:id");
        return $stmt->execute(array(":password"=>password_hash($newpw, PASSWORD_DEFAULT),":id"=>$this->id));
      }
    }
    return false;
  }

/**
 * @brief Hat der Benutzer Admin-Rechte
 * 
 * Ein Admin darf Einstellungen bearbeiten und neue Benutzer anlegen
 * @return bool
 */
  public function isAdmin()
  {
    return strpos($this->rights, 'A') !== false;
  }

/**
 * @brief Gibt zurück, ob dies der erste Login des Benutzers isst
 * 
 * @return bool
 */
  public function firstLogin()
  {
    return $this->lastlogin == null;
  }

/**
 * @brief Liest alle Benutzer
 * 
 * @return array von User
 */
  public static function readAll()
  {
    $stmt = Db::prepare("SELECT * FROM user");
    $stmt->execute();
    return $stmt->fetchAll(\PDO::FETCH_CLASS, get_class());
  }
  
/**
 * @brief Prüft, ob ein Login vergeben ist
 * 
 * @return bool
 */
  public static function loginExists($login)
  {
    $stmt = Db::prepare("SELECT login FROM user WHERE login=:login");
    $stmt->execute(array(":login"=>$login));
    return $stmt->fetch() !== false;
  }
  
  /**
   * @brief Legt einen neuen Benutzer an
   * 
   * @return bool
   */
  public static function newUser($login, $name, $rights)
  {
    $token = bin2hex(random_bytes(32));
    $hash = hash("sha256", $token);
    $stmt = Db::prepare("INSERT INTO user (login,name,token,rights) VALUES (:login,:name,:token,:rights)");
    if ($stmt->execute(array(":login"    => $login,
                             ":name"     => $name,
                             ":token"    => $hash,
                             ":rights"   => $rights))) {
      Log::info("user '$login' (".Db::lastInsertId().") created");
      $url = "/";
      if (isset($_SERVER['SERVER_NAME'])) {
        $url = "http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']);
      }
      $mailSubject = sprintf(_("Neuer Zugang zu %s"), K_PRODUCT_NAME);
      $mailText = sprintf(_("Hallo,\r\nSie (oder jemand anders) hat gerade einen Zugang zu %s registriert. ".
          "Um ihn zu aktivieren, folgen Sie diesem Link:\r\n%s%s\r\n".
          "Dies ist eine automatisch generierte Mail. Antworten werden nicht zugestellt.\r\n"),
        K_PRODUCT_NAME,
        "$url/index/activate/?token=",
        $token);
      \Pizza\Library\Mailer::mail($login, $mailSubject, $mailText);
      return true;
    }
    return false;
  }

/**
 * @brief Benutzer aktivieren
 * 
 * Bei der Aktivierung setzt der Benutzer ein Passwort, das Aktivierungstoken kann gelöscht werden
 * @param string $password Das neue Passwort des Benutzers
 * @return bool
 */
  public function activate($password)
  {
    if ($this->changePw(null, $password)) {
      Log::info("user '{$this->login}' ({$this->id}) activated");
      $this->doLogin();
      $_SESSION['user'] = $this;
      return true;
    }
    return false;
  }

  /**
   * @brief Ändert einen Benutzer
   * 
   * Zur Verwendung durch den Admin, um Rechte zu ändern
   * @return bool
   */
  public function change($name, $rights, $disabled=0)
  {
    $stmt = Db::prepare("UPDATE user SET name=:name,rights=:rights,disabled=:disabled ".
      "WHERE id=:id AND school=:school");
    if ($stmt->execute(array(":id"       => $this->id,
                             ":name"     => $name,
                             ":rights"   => $rights,
                             ":disabled" => $disabled,
                             ":school"   => $_SESSION['user']->school))) {
      Log::info("user '{$this->login}' ({$this->id}) changed");
      return true;
    }
    return false;
  }
  
/**
 * @brief Schreibt die Einstellungen in die Db
 * 
 * Schreibt die Member-Variablen im Bereich "Einstellungen".
 * @return void
 */
  public function writeSettings()
  {
    $stmt = Db::prepare("UPDATE user SET notify_neworder=:neworder,notify_orderdue=:due,notify_orderready=:ready,notify_newfile=:file ".
      "WHERE id=:id");
    return $stmt->execute([":id"        => $this->id, 
                           ":neworder"  => $this->notify_neworder,
                           ":due"       => $this->notify_orderdue,
                           ":ready"     => $this->notify_orderready,
                           ":file"      => $this->notify_newfile]);
  }
  
/**
 * @brief Fordert eine Passwort-Reset-Email für die angegebene E-Mail-Adresse an
 * 
 * @param string $email 
 * @param string $ip IP-Adresse, von der die Anfrage kommt. Anzahl der Anfragen pro 24h ist begrenzt.
 * @param string $mailSubject, $mailText Betreff und Text der E-Mail, werden übergeben, damit hier nicht übersetzt werden muss
 * @return bool
 */
  public static function passwordResetEmail($email, $ip, $mailSubject, $mailText)
  {
    if (!User::loginExists($email)) 
      return false;
    // zunächst prüfen, ob von dieser IP schon Anfragen in den letzten 24 Stunden kamen
    $stmt = Db::prepare("SELECT COUNT(*) FROM user__passwordreset WHERE ip=? AND TIMESTAMPDIFF(HOUR, time, NOW())<24");
    $stmt->execute(array($ip));
    if ($stmt->fetchColumn(0)>=10) {
      return false;
    }
    // prüfen, ob für diese E-Mail schon Abfragen in den letzten 24 Stunden kamen
    $stmt = Db::prepare("SELECT COUNT(*) FROM user__passwordreset JOIN user ON user.id=user__passwordreset.user WHERE user.login=?");
    $stmt->execute(array($ip));
    if ($stmt->fetchColumn(0)>=3) {
      return false;
    }
    // Token generieren, E-Mail verschicken
    $token = bin2hex(random_bytes(32));
    $hash = hash("sha256", $token);
    \Pizza\Library\Mailer::mail($email, $mailSubject, sprintf($mailText, $token));
    $stmt = Db::prepare("INSERT INTO user__passwordreset (user,token,ip) SELECT id,:token,:ip FROM user WHERE login=:email");
    return $stmt->execute(array(":email"=>$email, ":token"=>$hash, ":ip"=>$ip));
  }
  
/**
 * @brief Prüft, ob das Passwort-Reset-Token in der Datenbank ist
 * 
 * @param string $token
 * @return bool
 */
  public static function validResetToken($token) 
  {
    $stmt = Db::prepare("SELECT EXISTS (SELECT * FROM user__passwordreset WHERE token=? AND resetted=0)");
    $stmt->execute(array(hash("sha256", $token)));
    return (boolean)$stmt->fetchColumn(0);
  }
  
/**
 * @brief Setzt das Passwort eines Benutzers zurück
 * 
 * @param string $token Token aus der Passwort-Reset-E-Mail
 * @param string $newPassword
 * @return bool
 */
  public static function passwordReset($token, $newPassword)
  {
    $hash = hash("sha256", $token);
    $stmt = Db::prepare("SELECT * FROM user__passwordreset WHERE token=? AND resetted=0");
    $stmt->execute(array($hash));
    if ($row = $stmt->fetch()) {
      if ($user = User::read($row['user'])) {
        if ($user->changePw(null, $newPassword)) {
          $_SESSION['user'] = $user;
          Log::info("password reset for user '{$user->login}' ({$user->id})");
          $stmt = Db::prepare("UPDATE user__passwordreset SET resetted=1 WHERE token=?");
          $stmt->execute(array($hash));
          return true;
        }
      }
    }
    return false;
  }
  
/**
 * @brief Prüft, ob die Änderung des Benutzers erlaubt ist
 * 
 * Es darf nicht der letzte Admin der Schule gelöscht oder deaktiviert werden oder Admin-Rechte entzogen bekommen.
 * @param string $newRights   Die Rechte des Benutzers nach der zu prüfenden Änderung
 * @param bool   $newDisabled Der Deaktviert-Zustand des Benutzers nach der zu prüfenden Änderung
 * @return bool
 */
  public function canBeChanged($newRights, $newDisabled)
  {
    if (!$this->isAdmin() || $this->disabled || !$newDisabled && strpos($newRights, 'A') !== false)
      return true;
    // prüfen, ob die Schule nach der Änderung keinen Admin mehr hat
    $stmt = Db::prepare("SELECT COUNT(*) FROM user WHERE school=:school AND disabled=0 AND INSTR(rights,'A')>0 AND id<>:id");
    $stmt->execute(array(":school"=>$this->school, ":id"=>$this->id));
    return $stmt->fetchColumn() > 0;
  }
  
/**
 * @brief Benutzer löschen
 * 
 * @return bool
 */
  public function delete()
  {
    Log::info("user '{$this->login}' ({$this->id}) deleted");
    $stmt = Db::prepare("DELETE FROM user WHERE id=:id");
    if ($stmt->execute(array(":id"=>$this->id))) {
      return true;
    }
    return false;
  }
}
