<?php
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;

class ItellaPickupsApi
{
  private $api_url;

  public function __construct()
  {
    $this->api_url = 'https://itellapickups.com/api/v1/';
  }

  public function request($url_action, $post_data)
  {
    $url = $this->api_url . $url_action;
    $headers = array();

    $headers[] = 'Accept: application/json';
    $headers[] = 'Content-Type: application/json';

    $options = array(
      CURLOPT_POST            => 1,
      CURLOPT_HEADER          => 0,
      CURLOPT_URL             => $url,
      CURLOPT_FRESH_CONNECT   => 1,
      CURLOPT_RETURNTRANSFER  => 1,
      CURLOPT_FORBID_REUSE    => 1,
      CURLOPT_TIMEOUT         => 30,
      CURLOPT_CONNECTTIMEOUT  => 2,
      CURLOPT_HTTPHEADER      => $headers,
      CURLOPT_POSTFIELDS      => json_encode($post_data),
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = json_decode(curl_exec($ch));
    if (curl_errno($ch)) {
      throw new ItellaException(curl_error($ch));
    }
    curl_close($ch);

    return $response;
  }
}
