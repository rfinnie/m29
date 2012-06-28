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

class M29 {
  # Options configurable via $config
  public $base_url = '';
  public $allowed_protocols = array('http', 'https', 'ftp', 'gopher');
  public $max_url_length = 2048;
  public $pdo_dsn = 'mysql:host=localhost;dbname=m29';
  public $pdo_username = 'm29';
  public $pdo_password = '';
  public $pdo_driver_options = array();

  # Internally used (but still public) options;
  public $dbh;
  public $dbh_connected = false;

  # When constructing the class instance, use $config to populate
  # config options.
  function __construct($config = array()) {
    if(!is_array($config)) {
      $config = array();
    }

    foreach(array(
      'base_url',
      'pdo_dsn',
      'pdo_username',
      'pdo_password',
      'pdo_driver_options',
      'allowed_protocols',
      'max_url_length'
    ) as $opt) {
      if(isset($config[$opt])) {
        $this->$opt = $config[$opt];
      }
    }

    # A somewhat crude attempt at guessing the base URL.  Please, set it
    # explicitly instead.
    if(!$this->base_url) {
      $proto = 'http';
      if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
        $https = 'https';
      }
      $portstring = '';
      if(isset($_SERVER['SERVER_PORT'])) {
        if(
          (($proto == 'http') && !($_SERVER['SERVER_PORT'] == 80))
          || (($proto == 'https') && !($_SERVER['SERVER_PORT'] == 443))
        ) {
          $portstring = ':' . $_SERVER['SERVER_PORT'];
        }
      }
      $this->base_url = $proto . '://' . $_SERVER['HTTP_HOST'] . $portstring;
    }

  }

  # Connect to the database
  public function db_connect() {
    if(!$this->dbh_connected) {
      $this->dbh = new PDO(
        $this->pdo_dsn,
        $this->pdo_username,
        $this->pdo_password,
        $this->pdo_driver_options
      );
      $this->dbh_connected = true;
    }
  }

  # RFC4648-compatible Base64 encoder
  public function base64_encode_url($in) {
    $out = base64_encode($in);
    $out = str_replace('+', '-', $out);
    $out = str_replace('/', '_', $out);
    $out = str_replace('=', '', $out);
    return($out);
  }

  # RFC4648-compatible Base64 decoder
  public function base64_decode_url($in) {
    $out = $in;
    $out = str_replace('-', '+', $out);
    $out = str_replace('_', '/', $out);
    return(base64_decode($out));
  }

  # Convert an integer into multiple binary chars
  public function int2chars($int) {
    $out = '';
    while($int > 255) {
      $out = sprintf("%c", ($int & 255)) . $out;
      $int = $int >> 8;
    }
    $out = sprintf("%c", $int) . $out;
    return($out);
  }

  # Convert multiple binary chars into an integer
  public function chars2int($chars) {
    $o = 0;
    $s = 0;
    while(strlen($chars) > 0) {
      $a = ord(substr($chars, -1, 1));
      $chars = substr($chars, 0, -1);
      $o = $a * pow(256, $s) + $o;
      $s++;
    }
    return($o);
  }

  # Return a string of random bytes
  public function randbytes($num_bytes = 8) {
    $out = '';
    for($i = 0; $i < $num_bytes; $i++) {
      $out .= chr(rand(0,255));
    }
    return($out);
  }

  # Encrypt data using AES-128, ECB mode (no IV), null terminated
  # padding
  public function encrypt($data, $key) {
    return(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB));
  }

  # Decrypt data encrypted using AES-128, ECB mode (no IV), null
  # terminated padding
  public function decrypt($data, $key) {
    return(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB));
  }

  # Given a long URL, return a short URL using a randomly-generated key
  public function insert_long_url($url) {
    $firstKey_bin = $this->randbytes(8);
    $secondKey_bin = $this->randbytes(8);
    $key = $firstKey_bin . $secondKey_bin;

    $valid_protocol = false;
    foreach($this->allowed_protocols as $proto) {
      if(substr($url, 0, strlen($proto) + 1) == "$proto:") {
        $valid_protocol = true;
        break;
      }
    }
    if(!$valid_protocol) {
      return(array('errors' => array("Invalid URL protocol")));
    }

    $longUrlEncrypted_bin = $this->encrypt($url, $key);
    return($this->insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin));
  }

  # Given longUrlEncrypted, firstKey and optionally secondKey, return
  # a short URL (possibly incomplete short URL if secondKey was not
  # specified).
  public function insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin = '') {
    if(!(strlen($firstKey_bin) == 8)) {
      return(array('errors' => array("firstKey must be 64 bits (8 bytes)")));
    }
    if($secondKey_bin) {
      if(strlen($longUrlEncrypted_bin) > $this->max_url_length) {
        return(array('errors' => array("URLs must be " . $this->max_url_length . " characters or less")));
      }
      if(!(strlen($secondKey_bin) == 8)) {
        return(array('errors' => array("secondKey must be 64 bits (8 bytes)")));
      }
      $key = $firstKey_bin . $secondKey_bin;
      $url = $this->decrypt($longUrlEncrypted_bin, $key);
      $url = str_replace(chr(0), '', $url);

      $valid_protocol = false;
      foreach($this->allowed_protocols as $proto) {
        if(substr($url, 0, strlen($proto) + 1) == "$proto:") {
          $valid_protocol = true;
          break;
        }
      }
      if(!$valid_protocol) {
        return(array('errors' => array("Invalid decryption keys or URL protocol")));
      }
    }

    $this->db_connect();
    $now = time();
    $sth = $this->dbh->prepare("insert into urls (created_at, hits, encrypted_url, first_key) values (?, 0, ?, ?)");
    $sth->bindValue(1, $now, PDO::PARAM_INT);
    $sth->bindValue(2, $longUrlEncrypted_bin, PDO::PARAM_STR);
    $sth->bindValue(3, $firstKey_bin, PDO::PARAM_STR);
    $sth->execute();

    $id = $this->dbh->lastInsertId();
    $idb64 = $this->base64_encode_url($this->int2chars($id));
    $base_url = $this->base_url;
    if($secondKey_bin) {
      $key2b64 = $this->base64_encode_url($secondKey_bin);
      $outurl = "$base_url/$idb64/$key2b64";
      $short_url_incomplete = false;
    } else {
      $outurl = "$base_url/$idb64";
      $short_url_incomplete = true;
    }

    $out = array(
      'short_url' => $outurl,
      'short_url_incomplete' => $short_url_incomplete,
      'errors' => array()
    );
    if($secondKey_bin) {
      $out['long_url'] = $url;
    }
    return($out);
  }

  # Given a full shortUrl, retrieve information about the longUrl
  public function process_short_url($shortUrl, $increment_hits = false) {
    if(!substr($shortUrl, 0, strlen($this->base_url)) == $this->base_url) {
      return(array('errors' => array("Cannot determine URL components")));
    }
    if(!preg_match('/^\/([A-Za-z0-9\_\-]+)\/([A-Za-z0-9\_\-]+)$/', substr($shortUrl, strlen($this->base_url)), $m)) {
      return(array('errors' => array("Cannot determine URL components")));
    }
    $id_bin = $this->base64_decode_url($m[1]);
    $secondKey_bin = $this->base64_decode_url($m[2]);

    if(!(strlen($secondKey_bin) == 8)) {
      return(array('errors' => array("secondKey must be 64 bits (8 bytes)")));
    }

    $this->db_connect();
    $sth = $this->dbh->prepare("select * from urls where id = ?");
    $sth->bindValue(1, $this->chars2int($id_bin), PDO::PARAM_INT);
    $sth->execute();

    $row = array();
    while($rowi = $sth->fetch()) { $row = $rowi; }
    $firstKey_bin = $row['first_key'];
    $longUrlEncrypted_bin_check = $row['encrypted_url'];
    $key = $firstKey_bin . $secondKey_bin;
    $longUrl = $this->decrypt($longUrlEncrypted_bin_check, $key);
    $longUrl = str_replace(chr(0), '', $longUrl);

    $valid_protocol = false;
    foreach($this->allowed_protocols as $proto) {
      if(substr($longUrl, 0, strlen($proto) + 1) == "$proto:") {
        $valid_protocol = true;
        break;
      }
    }
    if(!$valid_protocol) {
      return(array('errors' => array("Invalid decryption keys or URL protocol")));
    }

    $out = array(
      'long_url' => $longUrl,
      'created_at' => $row['created_at'],
      'accessed_at' => $row['accessed_at'],
      'hits' => $row['hits'],
      'errors' => array()
    );

    if($increment_hits) {
      $now = time();
      $sth = $this->dbh->prepare("update urls set accessed_at = ?, hits = hits + 1 where id = ?");
      $sth->bindValue(1, $now, PDO::PARAM_INT);
      $sth->bindValue(2, $this->chars2int($id_bin), PDO::PARAM_INT);
      $sth->execute();
    }

    return($out);
  }

}
