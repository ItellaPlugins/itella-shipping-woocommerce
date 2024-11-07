<?php
// set to true in order to access examples (should never be true in production)
define('ITELLA_DEV', false);

if (!ITELLA_DEV) {
  die;
}

$email = ''; // email to test CallCourier

// Pakettikauppa testing user and secret
$p_user = ''; // Itella API username
$p_secret = ''; // Itella API password
$contract_2711 = ''; // Itella API contract for 2711 product
$contract_2317 = ''; // Itella API contract for 2317 product

$sample_track_nr = '';
$sample_track_nr_array = [];
