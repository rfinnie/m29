<?php

/**
 * M29 index page
 *
 * @package M29
 * @author Ryan Finnie <ryan@finnie.org>
 * @copyright 2012 Ryan Finnie
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */

// If you would like to use your own custom index page, duplicate the
// file to index.local.php.  Beware when upgrading though; while the
// code below is as simple as possible, incompatible changes may occur
// upon upgrading.

if(file_exists('index.local.php')) {
  require_once('index.local.php');
  exit(0);
}

require_once('config.php');
require_once('functions.php');
require_once('M29.php');

if(isset($_POST['url'])) {
  $m29 = new M29($config);
  try {
    $ret = $m29->insert_long_url($_POST['url']);
  } catch(M29Exception $e) {
    $error = $e->getMessage();
  }
}

?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Secure URL Shortener</title>
<link href='http://fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet' type='text/css'>
<link href="m29.css" rel="stylesheet" type="text/css"/>
</head>
<body>
<div id="container">
<h1>Secure URL Shortener</h1>

<div>
<form method="post">
<div>Enter a long URL to shorten:</div>
<div><input type="text" name="url" value="<?php echo (isset($_POST['url']) ? $_POST['url'] : '') ?>" style="width: 100%;" /></div>
<div style="text-align: right;"><input type="submit" value="Submit" /></div>
</form>
</div>

<?php if(isset($ret) && isset($ret['short_url'])) { ?>
<p class="center">The following short URL has been created:</p>
<p class="center"><a href="<?php echo $ret['short_url'] ?>" rel="nofollow"><?php echo $ret['short_url'] ?></a></p>
<?php } elseif(isset($error) && $error) { ?>
<p class="center"><strong>Error:</strong> <?php echo $error ?></p>
<?php } ?>

<h2>About</h2>

<p>This URL shortener uses the open source <a href="http://m29.us/">M29</a> software by <a href="http://www.finnie.org/">Ryan Finnie</a>.  This site is not run or endorsed by M29 or Ryan Finnie; please contact the site owner for support.</p>

</div>
</body>
</html>
