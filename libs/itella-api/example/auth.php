<?php
// TODO: TO BE REMOVED AS IT IS NO LONGER USED.
require '../vendor/autoload.php';

// env.php contains $user, $pass, $contract variables needed in these examples
if (!file_exists('env.php')) {
  copy('sample.env.php', 'env.php');
}
require('env.php');

$isTest = false;

$current_token = [];

$token_file = '../temp/token.json';

if (file_exists($token_file)) {
  echo 'Saved token found<br>';
  $current_token = json_decode(file_get_contents($token_file), true);
}

$auth = new \Mijora\Itella\Auth($user, $pass, $isTest);

/**
 * returns authorization array
 * [
 *  access_token  =>  string  // token string
 *  token_type    =>  string  // bearer
 *  expires_in    =>  number  // seconds untill token is invalid
 *  expires       =>  number  // unix timestamp when token becomes invalid
 * ]
 */
if (count($current_token) < 1 || $current_token['expires'] <= time()) {
  echo 'Getging new Token<br>';
  $authObj = $auth->getAuth();
  file_put_contents('token.json', json_encode($authObj));
  echo json_encode($authObj) . '<br>';
} else {
  echo 'Using saved Token<br>';
  $auth->setTokenArr($current_token);
}