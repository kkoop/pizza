<?php
namespace Pizza\Model;

class Debt
{
  public $user;
  public $amount;
  
  public static function getDebts()
  {
    $owedPerUser = Order::getOwedPerUser();           // Schulden anderer Nutzer bei uns
    $owingToUser = Order::getOwingToUser();           // unsere Schulden bei anderen Nutzern
    $paymentsPerUser = Payment::getPayedPerUser();    // Zahlungen anderer Nutzer an uns
    $paymentsToUser = Payment::getPayedToUser();      // Zahlungen an andere Nutzer
    
    $debts = array(); // Einträge: ['user'=>id, 'name'=>username, 'amount'=>betrag, positiv: wir bekommen Geld]
    foreach ($owedPerUser as $owed) {
      $debts[$owed['user']] = $owed;
    }
    foreach ($owingToUser as $owed) {
      if (!isset($debts[$owed['user']])) {
        $debts[$owed['user']] = $owed;
        $debts[$owed['user']]['amount'] = -$owed['amount'];
      } else {
        $debts[$owed['user']]['amount'] -= $owed['amount'];
      }
    }
    foreach ($paymentsPerUser as $payed) {
      if (!isset($debts[$payed['user']])) {
        $debts[$payed['user']] = $payed;
        $debts[$payed['user']]['amount'] = -$payed['amount'];
      } else {
        $debts[$payed['user']]['amount'] -= $payed['amount'];
      }
    }
    foreach ($paymentsToUser as $payed) {
      if (!isset($debts[$payed['user']])) {
        $debts[$payed['user']] = $payed;
      } else {
        $debts[$payed['user']]['amount'] += $payed['amount'];
      }
    }
    // leere Einträge entfernen
    $debts = array_filter($debts, function($item) {
      return $item['amount'] != 0.0;
    });
    return $debts;
  }
}
