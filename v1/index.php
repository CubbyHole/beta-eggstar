<?php
/**
 * Created by PhpStorm.
 * User: Crocell
 * Date: 24/01/14
 * Time: 11:56
 */

require 'Eggstar.class.php';

//Les requêtes faites en local (sur le même serveur donc) n'ont pas de header HTTP_ORIGIN
if (!array_key_exists('HTTP_ORIGIN', $_SERVER))
{
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try
{
    $API = new Eggstar($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
    $API->processAPI();
}
catch (Exception $e)
{
    echo json_encode(Array('error' => $e->getMessage()));
}