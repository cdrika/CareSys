<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('/', static fn() => redirect()->to('/app'));
$routes->group('', ['filter'=>'session'], static function($routes) {
    $routes->get('reference/locations','ReferenceController::locations');
    $routes->get('app','WorkspaceController::personal');
    $routes->get('app/(:segment)','WorkspaceController::personal/$1');
    $routes->post('actions/(:segment)','WorkspaceController::personalAction/$1');
    $routes->get('agency/(:segment)/history/(:segment)','WorkspaceController::history/$1/$2');
    $routes->get('agency/(:segment)/payroll/(:segment)/download','WorkspaceController::payrollDownload/$1/$2');
    $routes->get('agency/(:segment)/(:segment)','WorkspaceController::agency/$1/$2');
    $routes->post('agency/(:segment)/actions/(:segment)','WorkspaceController::agencyAction/$1/$2');
    $routes->get('api/v1/me/profile','ApiController::profile');
    $routes->patch('api/v1/me/profile','ApiController::updateProfile');
    $routes->post('api/v1/me/profile/publish','ApiController::publish');
    $routes->get('api/v1/agencies/(:segment)/candidates','ApiController::candidates/$1');
    $routes->post('api/v1/agencies/(:segment)/history-requests','ApiController::requestHistory/$1');
    $routes->get('api/v1/agencies/(:segment)/history-requests/(:segment)/entries','ApiController::readHistory/$1/$2');
    $routes->post('api/v1/me/history-requests/(:segment)/decision','ApiController::decideHistory/$1');
    $routes->post('api/v1/me/contracts/(:segment)/accept','ApiController::acceptPersonalContract/$1');
    $routes->post('api/v1/agencies/(:segment)/contracts/(:segment)/accept','ApiController::acceptAgencyContract/$1/$2');
    $routes->post('api/v1/me/joining-requests/(:segment)/decision','ApiController::decideJoining/$1');
    $routes->post('api/v1/agencies/(:segment)/joining/(:segment)/activate','ApiController::activate/$1/$2');
    foreach(['visits','clients','clock-events','leave-requests','assets','expenses','notes','payroll-exports','invoices'] as $operation)$routes->post('api/v1/agencies/(:segment)/'.$operation,'ApiController::operation/$1/'.$operation);
    $routes->post('api/v1/agencies/(:segment)/timesheets/(:segment)/approve','ApiController::operation/$1/time-approval/$2');
    $routes->post('api/v1/agencies/(:segment)/leave-requests/(:segment)/decision','ApiController::operation/$1/leave-decision/$2');
    $routes->post('api/v1/agencies/(:segment)/assets/(:segment)/custody','ApiController::operation/$1/asset-custody/$2');
    $routes->post('api/v1/agencies/(:segment)/expenses/(:segment)/decision','ApiController::operation/$1/expense-decision/$2');
    $routes->post('api/v1/agencies/(:segment)/invoices/(:segment)/receipt','ApiController::operation/$1/receipt/$2');
    $routes->post('api/v1/agencies/(:segment)/invoices/(:segment)/credit','ApiController::operation/$1/credit/$2');
});

service('auth')->routes($routes, ['except'=>['logout']]);
$routes->post('logout','\CodeIgniter\Shield\Controllers\LoginController::logoutAction',['filter'=>'session']);
