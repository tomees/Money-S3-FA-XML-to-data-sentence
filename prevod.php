<?php
include 'xmlVD.php';
include 'Latin1.php';

$zpracovani = new xmlVD();

$zpracovani->setFakturaCislo($_POST['cisloFaktury']);
$zpracovani->setTypDokladu($_POST['typDokladu']);
$zpracovani->zpracujDoklad();