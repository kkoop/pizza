<?php
namespace Pizza\Model;

/**
 * @brief Logging in der Datenbank
 * 
 */
class Log 
{
/**
 * @brief Loggt Ereignisse, die informativen Character haben, in der Datenbank.
 * 
 * @param string $text zu loggender Text
 * @param User   $user Benutzer, der die Log-Zeile ausgelöst hat, falls nicht gleich dem angemeldeten Benutzer
 * @return bool
 */
  public static function info($text, $user=null)
  {
    static $stmt = null;
    if ($stmt == null)
      $stmt = Db::prepare("INSERT INTO log (user,text) VALUES (:user,:text)");

    if ($user == null)
      $user = $_SESSION['user'] ?? null;
    return $stmt->execute(array(":user"=>$user->id ?? null, ":text"=>$text));
  }

/**
 * @brief Loggt katastrophale Ereignisse
 * 
 * Es wird eine E-Mail an den Entwickler gesendet (@sa global.php), sofern error_reporting nicht gesetzt ist.
 * Üblicherweise ist error_reporting auf einem Entwicklungssystem an, und auf einem Produktivsystem aus.
 * @return void
 */
  public static function fatal($text)
  {
    /*if (!ini_get("display_errors")) {
      \Pizza\Library\Mailer::mail(Config::get('emailDev'), "Fehler in ".K_PRODUCT_NAME."\n", $text);
    }*/
  }

/**
 * @brief Protokolliert die Ansicht eines Geräts für die Statistik
 * 
 * @param int $id Datenbank-ID des LD-Gerätes
 * @return void
 */
  public static function ldDeviceOpened($id)
  {
    if ($_SESSION['user']->kdnr > 0) {
      $stmt = Db::prepare("INSERT INTO ld__devstat (ref,date,opened) VALUES (?,CURDATE(),1) ".
        "ON DUPLICATE KEY UPDATE opened=opened+1");
      $stmt->execute(array($id));
    }
  }

/**
 * @brief Protokolliert die Ansicht eines Experiments für die Statistik
 * 
 * @param int $id Datenbank-ID des LD-Experiments
 * @return void
 */
  public static function ldExperimentOpened($id)
  {
    if ($_SESSION['user']->kdnr > 0) {
      $stmt = Db::prepare("INSERT INTO ld__expstat (ref,date,opened) VALUES (?,CURDATE(),1) ".
        "ON DUPLICATE KEY UPDATE opened=opened+1");
      $stmt->execute(array($id));
    }
  }


/**
 * @brief Handler für unbehandelte Ausnahmen
 * 
 * Ruft fatal() auf mit dem Text der Ausnahmen.
 * @return never
 */
  public static function exceptionHandler($ex) 
  {
    http_response_code(500);
    self::fatal($ex->__toString());
    // Ausnahme an System übergeben, damit sie im Apache-Log landet, und, falls display_errors an ist, auf dem Bildschirm
    throw $ex;
  }
}
