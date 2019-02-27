<?php
namespace Pizza;
use Pizza\Model;

require_once(__DIR__.'/../autoloader.php');
require_once(__DIR__.'/../config.php');
setlocale(LC_ALL, "de_DE.utf8");

class Maintenance 
{
  public function doMaintenance()
  {
    $this->checkOrderReady();
  }
  
  private function checkOrderReady()
  {
    foreach (Model\Orderday::readDue() as $day) {
      if ($day->getOrganizer()->notify_orderdue) {
        $msg = "Hallo,\r\n\r\ndie Bestellzeit der gemeinsamen Bestellung ist erreicht. Folgende Bestellungen liegen vor:\r\n";
        foreach ($day->getOrders() as $order) {
          $msg .= sprintf("* %s: %s%s, %.2f€\r\n",
                          $order->getUser()->name,
                          $order->product, 
                          !empty($order->comment) ? " ({$order->comment})" : "", 
                          $order->price);
        }
        \Pizza\Library\Mailer::mail($day->getOrganizer()->login, "Bestellung ist bereit", $msg);
      }
      $day->mailDueSent();
      // Speisekarte wird nicht mehr benötigt
      if ($menu = Model\Menu::read($day->url)) {
        $menu->delete();
      }
    }
  }
}

$m = new Maintenance;
$m->doMaintenance();
