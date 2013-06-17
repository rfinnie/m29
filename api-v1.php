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
  http_error(array(
    'reason' => 'notFound',
    'message' => 'json_encode is not found, please contact your server administrator',
  ));
}

if(isset($config['disable_api']) && $config['disable_api']) {
  http_error(array(
    'reason' => 'notFound',
    'message' => 'The API is disabled',
  ), 'json');
}

if($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
  http_error(array(
    'reason' => 'parseError',
    'message' => 'This API does not support parsing form-encoded input.',
  ), 'json');
}

if($_SERVER['PATH_INFO'] == '/url/history') {
  $out = array(
    'totalItems' => 0,
    'items' => array()
  );

  header("Content-Type: application/json; charset=UTF-8");
  print json_encode($out) . "\n";
  exit();
}

if(!($_SERVER['PATH_INFO'] == '/url')) {
  http_error(array(
    'reason' => 'notFound',
    'message' => 'The requested URL was not found on this server.',
  ), 'json');
}

$m29 = new M29($config);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  if(!($_SERVER['CONTENT_TYPE'] == 'application/json')) {
    http_error(array(
      'reason' => 'parseError',
      'message' => 'A Content-Type of application/json is required to POST to this method',
    ), 'json');
  }

  $json = json_decode($HTTP_RAW_POST_DATA, true);
  if(isset($json['longUrl'])) {
    $longUrl = $json['longUrl'];

    try {
      $ret = $m29->insert_long_url($longUrl);
    } catch(M29Exception $e) {
      http_error($e->error, 'json');
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
      http_error($e->error, 'json');
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
    http_error(array(
      'reason' => 'required',
      'message' => 'Required parameter: longUrl',
      'locationType' => 'parameter',
      'location' => 'longUrl',
    ), 'json');
  }
} else {
  if(isset($_GET['shortUrl'])) {
    try {
      $ret = $m29->process_short_url($_GET['shortUrl'], false);
    } catch(M29Exception $e) {
      http_error($e->error, 'json');
    }

    $out = array(
      'kind' => 'urlshortener#url',
      'id' => $_GET['shortUrl'],
      'longUrl' => $ret['long_url'],
      'status' => 'OK'
    );

    if(isset($_GET['projection']) && (($_GET['projection'] == 'FULL') || ($_GET['projection'] == 'ANALYTICS_CLICKS'))) {
      $out['created'] = gmdate('c', $ret['created_at']);
      if($ret['accessed_at']) {
        $out['lastClick'] = gmdate('c', $ret['accessed_at']);
      }
      $out['analytics'] = array(
        'allTime' => array(
          'shortUrlClicks' => $ret['hits'],
          'longUrlClicks' => $ret['hits']
        )
      );
    }

    header("Content-Type: application/json; charset=UTF-8");
    print json_encode($out) . "\n";
  } else {
    http_error(array(
      'reason' => 'required',
      'message' => 'Required parameter: shortUrl',
      'locationType' => 'parameter',
      'location' => 'shortUrl',
    ), 'json');
  }
}
