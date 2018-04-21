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
    $this->view->setVars(['startDate' => strftime("%x", $startTime),
                          'endDate'   => strftime("%x", $endTime),
                          'payments'  => Model\Payment::getForUser($startTime, $endTime)]);
                          
  }
  
  public function editAction()
  {
    $this->view->setVars(['title' => "Zahlungen"]);
    
  }
}
