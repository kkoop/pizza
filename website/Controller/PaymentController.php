<?php
namespace Pizza\Controller;
use Pizza\Model;

class PaymentController extends  Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Zahlungen"]);
    if (!empty($_REQUEST['startDate'])) {
      $startTime = strtotime($_REQUEST['startDate']);
      $endTime   = strtotime($_REQUEST['endDate']." 23:59:59");
      if (!($endTime > $startTime))
        $endTime = time();
    } else {
      $startTime = strtotime("-1 month");
      $endTime   = time();
    }
    $payments = Model\Payment::readAll($startTime, $endTime, $_SESSION['user']->id);
    $orders = Model\Orderday::readAll($startTime, $endTime);
    $orders = array_filter($orders, function($o) { return $o->organizer==$_SESSION['user']->id;});
    $transactions = array_merge($payments, $orders);
    usort($transactions, function($a, $b) {
      return $a->time-$b->time;
    });
    $balance = 0.0;
    foreach ($transactions as $transaction) {
      if (get_class($transaction) == "Pizza\Model\Payment" && $transaction->toId == $_SESSION['user']->id)
        $balance += $transaction->amount;
      else 
        $balance -= $transaction->amount;
    }
    $this->view->setVars(['startDate'    => strftime("%Y-%m-%d", $startTime),
                          'endDate'      => strftime("%Y-%m-%d", $endTime),
                          'transactions' => $transactions,
                          'balance'      => $balance]);
  }
  
  private function getDebts()
  {
    $owedPerUser = Model\Order::getOwedPerUser();           // Schulden anderer Nutzer bei uns
    $owingToUser = Model\Order::getOwingToUser();           // unsere Schulden bei anderen Nutzern
    $paymentsPerUser = Model\Payment::getPayedPerUser();    // Zahlungen anderer Nutzer an uns
    $paymentsToUser = Model\Payment::getPayedToUser();      // Zahlungen an andere Nutzer

    $debts = array(); // Einträge: ['user'=>id, 'name'=>username, 'amount'=>betrag, positiv: wir bekommen Geld]
    foreach ($owedPerUser as $owed) {
      $debts[$owed['user']] = $owed;
    }
    foreach ($paymentsPerUser as $payed) {
      if (!isset($debts[$payed['user']])) {
        $debts[$payed['user']] = $payed;
        $debts[$payed['user']]['amount'] = -$payed['amount'];
      } else {
        $debts[$payed['user']]['amount'] -= $payed['amount'];
      }
    }
    foreach ($owingToUser as $owed) {
      if (!isset($debts[$owed['user']])) {
        $debts[$owed['user']] = $owed;
        $debts[$owed['user']]['amount'] = -$owed['amount'];
      } else {
        $debts[$owed['user']]['amount'] -= $owed['amount'];
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

  public function openAction()
  {
    $this->view->setVars(['title' => "Offene Beträge",
                          'debts' => $this->getDebts()]);
  }

  public function addAction()
  {
    $this->view->setVars(['title' => "Zahlungen"]);
    if (isset($_POST['amount'])) {
      $count = 0;
      for ($i=0; $i<count($_POST['amount']); ++$i) {
        if ($_POST['user'][$i] && $_POST['amount'][$i] > 0) {
          if (Model\Payment::received($_POST['user'][$i], $_POST['amount'][$i])) {
            $count++;
          } else {
            $this->view->setError("Fehler beim Speichern der Zahlung");
          }
        }
      }
      $this->view->setVars(['successMessage' => sprintf("%d Zahlung(en) gespeichert.", $count)]);
    } else {
      $users = Model\User::readAll();
      $users = array_filter($users, function($item) {
        return $item->id != $_SESSION['user']->id;
      });
      $debts = array_filter($this->getDebts(), function($item) {
        return $item['amount'] > 0;
      });
      $this->view->setVars(['users' => $users, 
                            'debts' => $debts]);
    }
  }

  public function editAction()
  {
    $this->view->setVars(['title' => "Zahlungen"]);
    if (isset($_POST['amount'])) {
      if (Model\Payment::received($_POST['user'], $_POST['amount'])) {
        $this->view->setVars(['successMessage' => "Zahlung gespeichert."]);
      } else {
        $this->view->setError("Fehler beim Speichern der Zahlung");
      }
    } else {
      $users = Model\User::readAll();
      $users = array_filter($users, function($item) {
        return $item->id != $_SESSION['user']->id;
      });
      $this->view->setVars(['users' => $users]);
    }
  }
  
  public function adminAction()
  {
    if (!$_SESSION['user']->isAdmin()) {
      $this->view->setError("keine Rechte");
      return;
    }
    $this->view->setTitle("Zahlungen");
    if (!empty($_REQUEST['startDate'])) {
      $startTime = strtotime($_REQUEST['startDate']);
      $endTime   = strtotime($_REQUEST['endDate']." 23:59:59");
      if (!($endTime > $startTime))
        $endTime = time();
    } else {
      $startTime = strtotime("-1 month");
      $endTime   = time();
    }
    $users = Model\User::readAll();
    $payments = Model\Payment::readAll($startTime, $endTime, null);
    // bezahlte Beträge als Matrix
    $userMatrix = [];
    $userMap = [];
    foreach ($users as $user) {
      $userMap[$user->id] = $user->name;
      $userMatrix[$user->id] = [];
      foreach ($users as $user2) {
        $userMatrix[$user->id][$user2->id] = 0;
      }
    }
    $paymentMatrix = $userMatrix;
    foreach ($payments as $payment) {
      $paymentMatrix[$payment->fromId][$payment->toId] += $payment->amount;
    }
    // zu zahlende Beträge als Matrix
    $orderMatrix = $userMatrix;
    $orderDays = Model\Orderday::readAll($startTime, $endTime);
    foreach ($orderDays as $day) {
      foreach ($day->getOrders() as $order) {
        if ($order->user != $day->organizer)
          $orderMatrix[$order->user][$day->organizer] += $order->price;
      }
    }
    $this->view->setVars(['startDate'     => strftime("%Y-%m-%d", $startTime),
                          'endDate'       => strftime("%Y-%m-%d", $endTime),
                          'payments'      => $payments,
                          'userMap'       => $userMap,
                          'paymentMatrix' => $paymentMatrix,
                          'orderMatrix'   => $orderMatrix]);
  }
}
