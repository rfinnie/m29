<?php
# If you would like to use your own custom index page, duplicate the
# file to index.local.php.  Beware when upgrading though; while the
# code below is as simple as possible, incompatible changes may occur
# upon upgrading.

if(file_exists('index.local.php')) {
  require_once('index.local.php');
  exit(0);
}

########################################################################
# M29, a secure URL shortener
# Copyright (C) 2012 Ryan Finnie <ryan@finnie.org>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
# 02110-1301, USA.
########################################################################

require_once('config.php');
require_once('functions.php');
require_once('M29.php');

if(isset($_POST['url'])) {
  $m29 = new M29($config);
  $ret = $m29->insert_long_url($_POST['url']);
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
<?php } elseif(isset($ret) && count($ret['errors']) > 0) { ?>
<p class="center"><strong>Error!</strong></p>
<?php foreach($ret['errors'] as $error) { ?>
<p class="center"><strong>&raquo; <?php echo $error ?></strong></p>
<?php } ?>
<?php } ?>

<h2>About</h2>

<p>This URL shortener uses the open source <a href="http://m29.us/">M29</a> software by <a href="http://www.finnie.org/">Ryan Finnie</a>.  This site is not run or endorsed by M29 or Ryan Finnie; please contact the site owner for support.</p>

</div>
</body>
</html>
