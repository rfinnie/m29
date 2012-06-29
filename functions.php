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
 * Handle URL submissions
 *
 * This function is used by index.php to make the file as simple and
 * standardized as possible.
 *
 * @param string $long_url Long URL specified by the user
 * @param array $config Optional configuration array
 * @return array Tuple of short URL and error
 */
function url_submit($long_url, $config = array()) {
  $m29 = new M29($config);
  try {
    $ret = $m29->insert_long_url($long_url);
  } catch(M29Exception $e) {
    return(array('', $e->getMessage()));
  }
  return(array($ret['short_url'], ''));
}

/**
 * Outputs an HTTP 400 (Bad Request) error
 *
 * @param array $errors Error strings to output
 * @param bool $json Whether to output the error as a goo.gl-compatible
 *                   JSON object
 * @return void
 */
function http_400($errors, $json = false) {
  $outerrors = array();
  $lasterror = '';
  foreach($errors as $error) {
    $outerrors[] = array(
      'reason' => 'invalid',
      'message' => $error
    );
    $lasterror = $error;
  }

  header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
  if($json && function_exists('json_encode')) {
    $out = array(
      'error' => array(
        'errors' => $outerrors,
        'code' => 400,
        'message' => $lasterror
      )
    );

    header("Content-Type: application/json; charset=UTF-8");
    print json_encode($out) . "\n";
  } else {
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
  <title>400 Bad Request</title>
</head>

<body>
<h1>Bad Request</h1>
<p>The following errors have been produced:</p>
<pre><?php print_r($outerrors) ?></pre>
<?php echo $_SERVER['SERVER_SIGNATURE'] ?>
</body>
</html>
<?
  }
  exit();
}
