<?php

use Plinth\Main;

require_once '../const.php'; //File which contains all constant info & includes
require_once __VENDOR . 'autoload.php';

/* @var Main $main */
$main = new Main(false); //Create Main 

$main->handleRequest();

echo $main->getResponse()->render();