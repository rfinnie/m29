<?php

/**
 * M29 front-end shared functions
 *
 * This file contains functions used by multiple scripts in the
 * front-end code only.  The M29 class MUST NOT rely on any functions
 * defined here.
 *
 * @package M29
 * @author Ryan Finnie <ryan@finnie.org>
 * @copyright 2012 Ryan Finnie
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */

require_once('M29.php');

/**
 * Handle POSTs on the index page
 *
 * This function is used by index.php to handle various submission
 * methods, to make the index file itself as simple and standardized
 * as possible.
 *
 * @param array $config Optional configuration array
 * @return array Array of results and/or errors
 */
function index_handle_post($config = array()) {
  $m29 = new M29($config);
  $post = $_POST;
  $ret = array();
  if(isset($post['longUrlEncrypted']) && $post['longUrlEncrypted']) {
    $longUrlEncrypted_bin = $m29->base64_decode_url($post['longUrlEncrypted']);
    $firstKey_bin = $m29->base64_decode_url($post['firstKey']);
    if(isset($post['secondKey'])) {
      $secondKey_bin = $m29->base64_decode_url($post['secondKey']);
    } else {
      $secondKey_bin = '';
    }

    try {
      $ret = $m29->insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin);
    } catch(M29Exception $e) {
      $ret['error'] = $e->error;
    }
  } elseif(isset($post['longUrl']) && $post['longUrl']) {
    try {
      $ret = $m29->insert_long_url($post['longUrl']);
    } catch(M29Exception $e) {
      $ret['error'] = $e->error;
    }
  }

  if(isset($post['xhrRequest']) && ($post['xhrRequest'] == 'true')) {
    header("Content-Type: text/xml; charset=UTF-8");
    echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
    echo '<!--' . "\n";
    echo 'This response is for the benefit of the front page XMLHttpRequest.' . "\n";
    echo 'Please do not use this by third-party scripts.  A fully-featured JSON' . "\n";
    echo 'API is available; please see the main web site for details.' . "\n";
    echo '-->' . "\n";
    echo '<response>' . "\n";
    if(isset($ret['short_url']) && $ret['short_url']) {
      echo '<shortUrl>' . $ret['short_url'] . '</shortUrl>' . "\n";
      if(isset($ret['short_url_incomplete'])) {
        echo '<shortUrlIncomplete>' . ($ret['short_url_incomplete'] ? 'true' : 'false') . '</shortUrlIncomplete>' . "\n";
      }
    }
    if(isset($ret['error']) && $ret['error']) {
      echo '<error>' . htmlspecialchars($ret['error']) . '</error>' . "\n";
    }
    echo '</response>' . "\n";
    exit();
  }

  return($ret);
}

/**
 * Outputs an HTTP error
 *
 * @param array $error Error array
 * @param bool $type 'html' (default) or 'json'
 * @return void
 */
function http_error($error, $type = 'html') {
  $error['domain'] = 'global';
  if(in_array($error['reason'], array('required', 'invalid', 'parseError'))) {
    $code = 400;
    $codedesc = 'Bad Request';
  } elseif($error['reason'] == 'notFound') {
    $code = 404;
    $codedesc = 'Not Found';
  } else {
    $code = 503;
    $codedesc = 'Service Unavailable';
  }

  header($_SERVER['SERVER_PROTOCOL'] . " $code $codedesc");

  if(($type == 'json') && function_exists('json_encode')) {
    header('Content-Type: application/json; charset=UTF-8');
    $out = array(
      'error' => array(
        'errors' => array($error),
        'code' => $code,
        'message' => $error['message']
      )
    );
    print json_encode($out) . "\n";
  } else {
    header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $code; ?> <?php echo $codedesc; ?></title>
</head>

<body>
<h1><?php echo $codedesc; ?></h1>
<p><?php echo htmlentities($error['message']); ?></p>
<pre><?php print_r($error) ?></pre>
<?php echo $_SERVER['SERVER_SIGNATURE'] ?>
</body>
</html>
<?
  }
  exit();
}
