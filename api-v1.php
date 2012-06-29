<?php

/**
 * M29 goo.gl-compatible API
 *
 * @package M29
 * @author Ryan Finnie <ryan@finnie.org>
 * @copyright 2012 Ryan Finnie
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */

require_once('config.php');
require_once('functions.php');
require_once('M29.php');

if(!function_exists('json_encode')) {
  http_400(array('json_encode is not found, please contact your server administrator'));
}

if(isset($config['disable_api']) && $config['disable_api']) {
  http_400(array('The API is disabled'), true);
}

if(!($_SERVER['PATH_INFO'] == '/url')) {
  http_400(array('Invalid method'), true);
}

$m29 = new M29($config);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if(!($_SERVER['CONTENT_TYPE'] == 'application/json')) {
    http_400(array('A Content-Type of application/json is required to POST to this method'), true);
  }

  $json = json_decode($HTTP_RAW_POST_DATA, true);
  if(isset($json['longUrl'])) {
    $longUrl = $json['longUrl'];

    try {
      $ret = $m29->insert_long_url($longUrl);
    } catch(M29Exception $e) {
      http_400(array($e->getMessage()), true);
    }

    $out = array(
      'kind' => 'urlshortener#url',
      'id' => $ret['short_url'],
      'longUrl' => $longUrl
    );
    header("Content-Type: application/json; charset=UTF-8");
    print json_encode($out) . "\n";
  } elseif(isset($json['longUrlEncrypted']) && isset($json['firstKey'])) {
    $longUrlEncrypted_bin = $m29->base64_decode_url($json['longUrlEncrypted']);
    $firstKey_bin = $m29->base64_decode_url($json['firstKey']);
    if(isset($json['secondKey'])) {
      $secondKey_bin = $m29->base64_decode_url($json['secondKey']);
    } else {
      $secondKey_bin = '';
    }

    try {
      $ret = $m29->insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin);
    } catch(M29Exception $e) {
      http_400(array($e->getMessage()), true);
    }

    if($secondKey_bin) {
      $out = array(
        'kind' => 'urlshortener#url',
        'id' => $ret['short_url'],
        'longUrl' => $ret['long_url']
      );
      header("Content-Type: application/json; charset=UTF-8");
      print json_encode($out) . "\n";
    } else {
      $out = array(
        'kind' => 'urlshortener#url',
        'id' => $ret['short_url'],
        'idIncomplete' => True
      );
      header("Content-Type: application/json; charset=UTF-8");
      print json_encode($out) . "\n";
    }
  } else {
    http_400('This method requires either longUrl or longUrlEncrypted/firstKey', true);
  }
} else {
  if(isset($_GET['shortUrl'])) {
    try {
      $ret = $m29->process_short_url($_GET['shortUrl'], false);
    } catch(M29Exception $e) {
      http_400(array($e->getMessage()), true);
    }

    $out = array(
      'kind' => 'urlshortener#url',
      'id' => $_GET['shortUrl'],
      'longUrl' => $ret['long_url'],
      'status' => 'OK'
    );

    if(isset($_GET['projection']) && (($_GET['projection'] == 'FULL') || ($_GET['projection'] == 'ANALYTICS_CLICKS'))) {
      $out['created'] = gmdate('c', $ret['created_at']);
      $out['analytics'] = array(
        'allTime' => array(
          'shortUrlClicks' => $ret['hits']
        )
      );
    }

    header("Content-Type: application/json; charset=UTF-8");
    print json_encode($out) . "\n";
  } else {
    http_400(array('This method requires shortUrl'), true);
  }
}
