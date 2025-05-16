<?php
namespace Mijora\Itella;

use Mijora\Itella\ItellaException;

class ItellaPickupsApi
{
  private $api_url = 'https://itellapickups.com/api/v1/';
  private $token;

  public function __construct($api_url = false)
  {
    if (!empty($api_url)) {
      $this->api_url = $api_url;
    }
  }

  public function setToken($token)
  {
    $this->token = $token;
    return $this;
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

    if (is_object($response)) {
      if (property_exists($response, 'status')) {
        if ($response->status == 'ok') {
          return array(
            'status' => '200',
            'message' => 'Request received successfully!'
          );
        } else {
          return array(
            'status' => '500',
            'message' => 'Unknown response status - ' . $response->status
          );
        }
      } else {
        return array(
            'status' => '500',
            'message' => 'Unknown response object'
          );
      }
    }

    return $response;
  }

  public function requestXml($url_action, $post_data)
  {
    $url = $this->api_url . $url_action;
    $headers = array();

    $headers[] = 'Content-type: text/xml; charset=utf-8';
    if (!empty($this->token)) {
      $headers[] = 'Authorization: Bearer ' . $this->token;
    }

    $options = array(
      CURLOPT_POST            => 1,
      CURLOPT_HEADER          => 0,
      CURLOPT_URL             => $url,
      CURLOPT_FRESH_CONNECT   => 1,
      CURLOPT_RETURNTRANSFER  => 1,
      CURLOPT_FORBID_REUSE    => 1,
      CURLOPT_TIMEOUT         => 30,
      CURLOPT_HTTPHEADER      => $headers,
      CURLOPT_POSTFIELDS      => $post_data,
    );

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $xml = $this->parse_response_xml($response);
    if ($xml !== false) {
      $status = (string) $xml->result;
      $message = (string) $xml->result_message;
      if ($status == 'FAILURE') {
        return array(
          'status' => '500',
          'message' => $status . ' - ' . $message,
        );
      } else if ($status == 'SUCCESS') {
        return array(
          'status' => '200',
          'message' => $message,
        );
      } else {
        return array(
           'status' => '500',
           'message' => 'Unknown response status - ' . $status,
         );
      }
    }
    return json_decode($response, true);
  }

  private function parse_response_xml($xml_data) {
    $response = str_replace('resultMessage', 'result_message', $xml_data);
    
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($response);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    return ($doc !== false && empty($errors)) ? $doc : false;
  }
}
