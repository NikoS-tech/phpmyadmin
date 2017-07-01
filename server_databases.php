<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Handles server databases page.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\server\ServerDatabasesController;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\server\ServerDatabasesController'
);
$container->alias(
    'ServerDatabasesController',
    'PMA\libraries\controllers\server\ServerDatabasesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerDatabasesController $controller */
$controller = $container->get(
    'ServerDatabasesController', array()
);
$controller->indexAction();
