<?php
namespace Pizza\Library;

/**
 * @brief Ausnahme zur Umleitung auf Anmeldeseite
 * 
 * Wird ausgelöst, wenn kein Benutzer angemeldet ist, die aktuelle Seite aber eine Anmeldung benötigt.
 */
class NeedsLoginException extends \Exception
{
/**
 * @brief URL, auf die nach Login umgeleitet werden soll
 * 
 * @return string
 */
  public function getRedirectUrl()
  {
    $url = $_GET['_url'] ?? '';
    $queryString = "/?";
    foreach ($_GET as $p => $v) {
      if ($p != "_url")
        $queryString .= urlencode($p) ."=". urlencode($v);
    }
    if ($queryString == "/?") 
      $queryString = "";
    return "/$url".$queryString;
  }
}
