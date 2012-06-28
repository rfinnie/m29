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

# This file contains functions used by multiple scripts in the
# front-end code only.  The M29 class MUST NOT rely on any functions
# defined here.

# Return a HTTP 400 (Bad Request) error, given an array of error
# messages.
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
