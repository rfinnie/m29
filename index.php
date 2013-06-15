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

$ret = index_handle_post($config);
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>Secure URL Shortener</title>
<link href='http://fonts.googleapis.com/css?family=Ubuntu+Mono:400,700' rel='stylesheet' type='text/css'>
<link href="m29.css" rel="stylesheet" type="text/css"/>
<script src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/rollups/aes.js"></script>
<script src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/components/mode-ecb-min.js"></script>
<script src="http://crypto-js.googlecode.com/svn/tags/3.0.2/build/components/pad-zeropadding-min.js"></script>
<script src="m29.js"></script>
</head>
<body onload="return prepareSubmitForm(document.getElementById('submitForm'), document.getElementById('responseResults'));">
<div id="container">
<h1>Secure URL Shortener</h1>

<div>
<form id="submitForm" method="post">
<div>Enter a long URL to shorten:</div>
<div><input type="text" name="longUrl" /></div>
<div id="submitLine"><input type="submit" name="submitButton" value="Submit" onclick="return submitEncryptedUrl(this.form, document.getElementById('responseResults'));" /></div>
</form>
</div>

<div id="responseResults">
<?php
if(isset($ret['short_url']) && $ret['short_url']) {
  ?>
  <p>The following short URL has been created:</p>
  <p><a href="<?php echo $ret['short_url'] ?>" rel="nofollow"><?php echo $ret['short_url'] ?></a></p>
  <?php
} elseif(isset($ret['error']) && $ret['error']) {
  ?>
  <p><strong>Error:</strong> <?php echo $ret['error'] ?></p>
  <?php
}
?>
</div>

<h2>About</h2>

<p>This URL shortener uses the open source <a href="http://m29.us/">M29</a> software by <a href="http://www.finnie.org/">Ryan Finnie</a>.  This site is not run or endorsed by M29 or Ryan Finnie; please contact the site owner for support.</p>

</div>
</body>
</html>
