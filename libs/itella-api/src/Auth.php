<?php
// TODO: TO BE REMOVED AS IT IS NO LONGER USED.
namespace Mijora\Itella;

class Auth
{
  private $user;
  private $pass;
  private $isTest = false;

  private $tokenArr;

  public function __construct($user, $pass, $isTest = false)
  {
    $this->user = $user;
    $this->pass = $pass;
    $this->isTest = $isTest;
  }

  public function getAuth()
  {
    $url = 'https://oauth.' . ($this->isTest ? 'barium.' : '') . 'posti.com/oauth/token?grant_type=client_credentials';
    $timestamp = time();

    $headers = array(
      "Accept: application/json;charset=\"utf-8\"",
      "Cache-Control: no-cache",
      "Pragma: no-cache",
    );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($curl);
    
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    $response = json_decode($response, true);

    if (isset($response['access_token'])) {
      $response['expires'] =  $timestamp + $response['expires_in'];
      $this->tokenArr = $response;
    }

    return $response;
  }

  public function isValid()
  {
    if (!isset($this->tokenArr['expires']) || $this->tokenArr['expires'] <= time())
      return false;
    
    return true;
  }

  /**
   * Set token array from previous authentication.
   * 
   * @param array $tokenArray token array received previously from getAuth
   */
  public function setTokenArr($tokenArray)
  {
    if (isset($tokenArray['access_token']))
    $this->tokenArr = $tokenArray;
  }

  public function getTokenArr()
  {
    return $this->tokenArr;
  }

  public function getToken()
  {
    return $this->tokenArr['access_token'];
  }
}