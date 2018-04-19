<?php
namespace Pizza\Library;

/**
 * @brief Übernimmt das Versenden von E-Mails
 * 
 * In erster Linie, damit man E-Mails bei Unit-Tests abfangen kann, siehe dort Klasse MailMock.
 */
class Mailer
{
  protected static $instance;

  protected static function getInstance()
  {
    if (self::$instance === null) 
      self::$instance = new self();
    return self::$instance;
  }

  public static function mail($to, $subject, $message)
  {
    self::getInstance()->doMail($to, $subject, $message);
  }

  protected function doMail($to, $subject, $message)
  {
    mail($to, $subject, $message . K_MAIL_SIGNATURE,
      "From: ".K_MAIL_FROM."\r\n".
      "Content-type: text/plain; charset=utf-8 \r\n".
      "Date: ".date("r (T)")."\r\n");
  }
}
