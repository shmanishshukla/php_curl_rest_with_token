<?php

/**
 * PHP Rest CURL.
 *
 * @author Manish Shukla [sh.manishshukla@gmail.com]
 *
 * Example Header for authtoken
 * $headers = array(
 * 'Content-Type: application/x-www-form-urlencoded',
 * 'Accept: application/json',
 * 'Cache-Control: no-cache',
 * );
 *
 * Example Param for authtoken
 * $param = array(
 * 'grant_type' => 'password',
 * 'username' => 'user1',
 * 'password' => 'pass1',
 * );
 *
 * Exaple Header for rest curl
 * $headers = array(
 * 'Authorization: Bearer ' . $this->authToken,
 * 'x-tc-userid: ' . $this->profileId,
 * 'Content-Type: application/json',
 * );
 */
class RestCurl {

  private $authToken = '';
  private $tokenError = FALSE;

  /**
   * __Construct : Generate token.
   */
  public function __construct($url, $headers, $param = array()) {
    $this->generateAuthToken($url, $headers, $param);
  }

  /**
   * Get token error info.
   */
  public function getTokenError() {
    return $this->tokenError;
  }

  /**
   * Generate Auth Token.
   */
  public function generateAuthToken($url, $headers, $param) {

    // Init curl request.
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_POST, TRUE);
    curl_setopt($handle, CURLOPT_POSTFIELDS, rawurldecode(http_build_query($param)));
    curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
    $response = curl_exec($handle);

    if (curl_error($handle)) {
      $this->tokenError = curl_error($handle);
    }
    else {
      $this->authToken = json_decode($response)->access_token;
    }

    curl_close($handle);
  }

  /**
   * @method Call the exec method
   */
  public function execRest($method, $url, $headers, $obj = array()) {

    // If error in token generatoin.
    if ($this->tokenError === TRUE && empty($this->authToken)) {
      return $this->tokenError;
    }

    // Get the auth token.
    $headers[] = 'Authorization: Bearer ' . $this->authToken;

    $curl = curl_init();

    switch ($method) {
      case 'GET':
        if (strrpos($url, "?") === FALSE) {
          $url .= '?' . rawurldecode(http_build_query($obj));
        }
        break;

      case 'POST':
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
        break;

      case 'PUT':
      case 'DELETE':
      default:
        // Method.
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        // body.
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

    // Ed. array('Accept: application/json', 'Content-Type: application/json')
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);

    // Exec.
    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    // Data.
    $header = trim(substr($response, 0, $info['header_size']));
    $body = substr($response, $info['header_size']);

    return array('status' => $info['http_code'], 'header' => $header, 'data' => json_decode($body));
  }

}
