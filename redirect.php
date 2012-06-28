<?php
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
