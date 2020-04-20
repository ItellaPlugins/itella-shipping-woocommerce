<?php
// set to true in order to access examples (should never be true in production)
define('ITELLA_DEV', false);

if (!ITELLA_DEV) {
  die;
}

$is_test = false;

$user = ''; // Itella API username
$pass = ''; // Itella API password
$contract = ''; // contract number extracted from tracking number. NO LONGER USED
$email = ''; // email to test CallCourier

// Pakettikauppa testing user and secret
$p_user = '';
$p_secret = '';

$sample_track_nr = '';
$sample_track_nr_array = [];
