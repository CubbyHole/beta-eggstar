<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 18/02/14
 * Time: 18:57
 */

$projectRoot = $_SERVER['DOCUMENT_ROOT'].'/eggstar/v2';

require_once '/Interfaces/AccountManager.interface.php';
require_once '/Interfaces/RefPlanManager.interface.php';
require_once '/Interfaces/UserManager.interface.php';
require_once '/Interfaces/ElementManager.interface.php';
require_once '/Interfaces/RefElementManager.interface.php';

require_once '/Managers/AbstractManager.class.php';
require_once '/Managers/AccountManager.class.php';
require_once '/Managers/RefPlanManager.class.php';
require_once '/Managers/UserManager.class.php';
require_once '/Managers/ElementManager.class.php';
require_once '/Managers/RefElementManager.class.php';