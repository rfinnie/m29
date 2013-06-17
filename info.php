<?php

/**
 * M29 URL info page
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
  $ret = $m29->process_short_url($url, false);
} catch(M29Exception $e) {
  http_error($e->error);
}

header($_SERVER['SERVER_PROTOCOL'] . ' 301 Moved Permanently');
#header("Location: " . $ret['long_url']);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>URL Information</title>
<link href='http://fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet' type='text/css'>
<link href="<?php echo $m29->base_url; ?>/m29.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="container">
<h1>URL Information</h1>
<ul>
<li>Short URL: <a href="<?php echo htmlentities($url); ?>" rel="nofollow"><?php echo htmlentities($url); ?></a></li>
<li><div class="scrolllong">Long URL: <a href="<?php echo htmlentities($ret['long_url']); ?>" class="smalltext" rel="nofollow"><?php echo htmlentities($ret['long_url']); ?></a></div></li>
<?php if($ret['created_at']) { ?><li>Created: <?php echo gmdate('r', $ret['created_at']); ?></li><?php } ?>
<?php if($ret['accessed_at']) { ?><li>Last click: <?php echo gmdate('r', $ret['accessed_at']); ?></li><?php } ?>
<li>Total clicks: <?php echo $ret['hits'] + 0; ?></li>
</ul>
</div>
</body>
</html>
