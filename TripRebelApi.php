<?php
/**
 * Class TripRebelApi. Implements TripRebel API actions.
 */
class TripRebelApi {

  const API_URL = 'http://tourmaster.services.triprebel.com/';
  const CLIENT_ID = 'YOUR_CLIENT_ID';
  const APP_KEY = 'YOUR_APP_KEY';
  const AUTH_URL = 'https://login.microsoftonline.com/accounts.triprebel.com/oauth2/token';
  const RESOURCE_ID = '633dca92-ffe8-4591-a9d4-c13e97445c31';
  
  /**
   * Tests if current API credentials are valid.
   *
   * @return bool
   */
  public function testAccount() {
    $result = $this->apiRequest('orders/test', array(), 'GET', FALSE);
    return is_array($result) && strtolower($result['response']) == 'ok';
  }

  /**
   * Makes request to API endpoint.
   *
   * @param string $action
   * @param array $params
   * @param string $method
   * @param bool $decode
   * @return array|bool
   */
  protected function apiRequest($action, $params = array(), $method = 'POST', $decode = TRUE) {
    if ($token = $this->getAccessToken()) {
      $url = static::API_URL . $action;
      $initCurl = curl_init();
      curl_setopt_array($initCurl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_VERBOSE => 1,
        CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $token),
      ));
      if ($method == 'POST') {
        $request_body = json_encode($params);
        curl_setopt($initCurl, CURLOPT_POST, TRUE);
        curl_setopt($initCurl, CURLOPT_POSTFIELDS, $request_body);
        curl_setopt($initCurl, CURLOPT_HTTPHEADER, array(
          "Authorization: Bearer " . $token,
          "Content-Type: application/json",
        ));
      }
      $response = curl_exec($initCurl);
      $result['original_response'] = $response;
      $result['response'] = $decode ? json_decode($response, TRUE) : $response;
      $result['code'] = curl_getinfo($initCurl, CURLINFO_HTTP_CODE);
      curl_close($initCurl);

      return count($result) === 0 ? FALSE : $result;
    }

    return FALSE;
  }
  
  /**
   * Gets access token.
   *
   * @return string|null
   */
  protected function getAccessToken() {
    $token = isset($_SESSION['triprebel_access_token']) ? $_SESSION['triprebel_access_token'] : NULL;
    $expiration_time = isset($_SESSION['triprebel_access_token_expire']) ? $_SESSION['triprebel_access_token_expire'] : time();
    if (!$token || $expiration_time <= time()) {
      $params = array(
        "grant_type" => "client_credentials",
        "client_id" => static::CLIENT_ID,
        "client_secret" => static::APP_KEY,
        "resource" => static::RESOURCE_ID,
      );
      $initCurl = curl_init();
      curl_setopt_array($initCurl, array(
        CURLOPT_URL => static::AUTH_URL,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_VERBOSE => 1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $params,
        CURLOPT_SSL_VERIFYPEER => FALSE,
      ));
      $time = time();
      $response = json_decode(curl_exec($initCurl), TRUE);
      curl_close($initCurl);
      if (isset($response['error'])) {
        $token = NULL;
      } else {
        $token = $response['access_token'];
        $_SESSION['triprebel_access_token'] = $token;
        $_SESSION['triprebel_access_token_expire'] = $response['expires_in'] + $time;
      }
    }

    return $token;
  }
}