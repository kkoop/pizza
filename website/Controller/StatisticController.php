<?php
namespace Pizza\Controller;
use Pizza\Model;

class StatisticController extends Controller
{
  public function indexAction()
  {
    $this->view->setVars(['title'          => "Statistik",
                          'favouritesUser' => Model\Statistic::favouriteOrdersUser(),
                          'favouritesAll'  => Model\Statistic::favouriteOrdersAll(),
                          'services'       => Model\Statistic::favouriteDeliveryServices()]);
  }
}
