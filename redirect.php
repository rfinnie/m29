<?php

/**
 * M29 redirector
 *
 * @package M29
 * @author Ryan Finnie <ryan@finnie.org>
 * @copyright 2012 Ryan Finnie
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */

require_once('config.php');
require_once('functions.php');
require_once('M29.php');

$m29 = new M29($config);

if(isset($_SERVER['PATH_INFO'])) {
  $url = $m29->base_url . $_SERVER['PATH_INFO'];
} else {
  http_error(array(
    'reason' => 'serviceError',
    'message' => 'Cannot determine the short URL',
  ));
}

try {
  $ret = $m29->process_short_url($url, true);
} catch(M29Exception $e) {
  http_error($e->error);
}

header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
header("Location: " . $ret['long_url']);
?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
  <title>301 Moved Permanently</title>
</head>

<body>
<h1>Moved Permanently</h1>
<p>The document has moved <a href="<?php echo htmlentities($ret['long_url']) ?>">here</a>.</p>
<?php echo $_SERVER['SERVER_SIGNATURE'] ?>
</body>
</html>
