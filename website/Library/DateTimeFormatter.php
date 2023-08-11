<?php
namespace Pizza\Library;

class DateTimeFormatter
{
  public static function DateFormatter()
  {
    return \IntlDateFormatter::create(null, 
      \IntlDateFormatter::MEDIUM, 
      \IntlDateFormatter::NONE,
      $_SESSION['user'] ? $_SESSION['user']->timezone : null);
  }
  
  public static function DateTimeFormatter()
  {
    return \IntlDateFormatter::create(null, 
      \IntlDateFormatter::MEDIUM, 
      \IntlDateFormatter::SHORT,
      $_SESSION['user'] ? $_SESSION['user']->timezone : null);
  }
  
  public static function TimeFormatter()
  {
    return \IntlDateFormatter::create(null, 
      \IntlDateFormatter::NONE, 
      \IntlDateFormatter::SHORT,
      $_SESSION['user'] ? $_SESSION['user']->timezone : null);
  }
}
