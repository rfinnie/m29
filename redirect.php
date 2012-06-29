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
  http_400(array('Cannot determine the short URL'));
}

$ret = $m29->process_short_url($url, true);
if(count($ret['errors']) > 0) {
  http_400($ret['errors']);
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
<p>The document has moved <a href="<?php echo $ret['long_url'] ?>">here</a>.</p>
<?php echo $_SERVER['SERVER_SIGNATURE'] ?>
</body>
</html>
