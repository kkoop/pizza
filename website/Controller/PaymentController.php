<?php
namespace Pizza\Controller;
use Pizza\Model;

class PaymentController extends  Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title' => "Zahlungen"]);
    $startTime = strtotime("-1 month");
    $endTime   = time();
    $payments = Model\Payment::getForUser($startTime, $endTime);
    $orders = Model\Orderday::readAll($startTime, $endTime);
    $transactions = array_merge($payments, $orders);
    usort($transactions, function($a, $b) {
      return $a->time-$b->time;
    });
    $this->view->setVars(['startDate'    => strftime("%x", $startTime),
                          'endDate'      => strftime("%x", $endTime),
                          'transactions' => $transactions]);
                          
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
      $owedPerUser = Model\Order::getOwedPerUser();
      $paymentsPerUser = Model\Payment::getPayedPerUser();
      $debts = array();
      foreach ($owedPerUser as $owed) {
        $debts[] = $owed;
        foreach ($paymentsPerUser as $payed) {
          if ($payed['user'] == $owed['user']) {
            $debts[count($debts)-1]['amount'] -= $payed['amount'];
            break;
          }
        }
      }
      var_dump($debts);
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
}
