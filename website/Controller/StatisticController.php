<?php
namespace Pizza\Controller;
use Pizza\Model;

class StatisticController extends Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title'      => "Statistik",
                          'favourites' => Model\Statistic::favouriteOrders(),
                          'services'   => Model\Statistic::favouriteDeliveryServices()]);
  }
}
