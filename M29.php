<?php

/**
 * M29 back-end class
 *
 * This class is a self-contained class used for serving data to M29
 * front-ends.
 *
 * @package M29
 * @author Ryan Finnie <ryan@finnie.org>
 * @copyright 2012 Ryan Finnie
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU GPL version 2
 */
class M29 {
  /**
   * The base URL (minus trailing slash) of the shortening service
   *
   * @var string
   */
  public $base_url = '';

  /**
   * URL protocols allowed to be stored or served
   *
   * @var array
   */
  public $allowed_protocols = array('http', 'https', 'ftp', 'gopher');

  /**
   * Maximum length of a submitted URL to store
   *
   * @var int
   */
  public $max_url_length = 2048;

  /**
   * The DSN to be used by PDO when connecting
   *
   * @var string
   */
  public $pdo_dsn = 'mysql:host=localhost;dbname=m29';

  /**
   * The username to be used by PDO when connecting
   *
   * @var string
   */
  public $pdo_username = 'm29';

  /**
   * The password to be used by PDO when connecting
   *
   * @var string
   */
  public $pdo_password = '';

  /**
   * Extra options to be used by PDO when connecting
   *
   * @var array
   */
  public $pdo_driver_options = array();

  /**
   * The PDO database object
   *
   * @var object
   */
  public $dbh;

  /**
   * Whether the database connection is established
   *
   * @var bool
   */
  public $dbh_connected = false;

  /**
   * Class constuctor
   *
   * @param array $config Optional array of configuration options.  If
   *                      configuration options are not provided, best
   *                      defaults will be used.
   * @return void
   */
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

    // A somewhat crude attempt at guessing the base URL.  Please, set it
    // explicitly instead.
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

  /**
   * Connect to the database
   *
   * @param void
   * @return void
   */
  public function db_connect() {
    if(!$this->dbh_connected) {
      $this->dbh = new PDO(
        $this->pdo_dsn,
        $this->pdo_username,
        $this->pdo_password,
        $this->pdo_driver_options
      );
      $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->dbh_connected = true;
    }
  }

  /**
   * RFC4648-compatible Base64 encoder
   *
   * @param string $in Binary input data
   * @return string RFC4648-compatible Base64-encoded data
   */
  public function base64_encode_url($in) {
    $out = base64_encode($in);
    $out = str_replace('+', '-', $out);
    $out = str_replace('/', '_', $out);
    $out = str_replace('=', '', $out);
    return($out);
  }

  /**
   * RFC4648-compatible Base64 decoder
   *
   * @param string $in RFC4648-compatible Base64-encoded data
   * @return string Binary data
   */
  public function base64_decode_url($in) {
    $out = $in;
    $out = str_replace('-', '+', $out);
    $out = str_replace('_', '/', $out);
    return(base64_decode($out));
  }

  /**
   * Convert an integer into multiple binary chars
   *
   * @param int $int Integer
   * @return string Binary characters
   */
  public function int2chars($int) {
    $out = '';
    while($int > 255) {
      $out = sprintf("%c", ($int & 255)) . $out;
      $int = $int >> 8;
    }
    $out = sprintf("%c", $int) . $out;
    return($out);
  }

  /**
   * Convert multiple binary chars into an integer
   *
   * @param string $chars Binary characters
   * @return int Integer
   */
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

  /**
   * Return a string of random bytes
   *
   * @param int $num_bytes Optional number of random bytes to generate
   * @return string Generated random bytes
   */
  public function randbytes($num_bytes = 8) {
    $out = '';
    for($i = 0; $i < $num_bytes; $i++) {
      $out .= chr(rand(0,255));
    }
    return($out);
  }

  /**
   * Encrypt data using AES-128, ECB mode (no IV), null terminated padding
   *
   * @param string $data Unencrypted data
   * @param string $key 128-bit key
   * @return string Encrypted data
   */
  public function encrypt($data, $key) {
    return(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB));
  }

  /**
   * Decrypt data encrypted using AES-128, ECB mode (no IV), null
   * terminated padding
   *
   * @param string $data Encrypted data
   * @param string $key 128-bit key
   * @return string Unencrypted data
   */
  public function decrypt($data, $key) {
    return(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_ECB));
  }

  /**
   * Shorten a long URL using a randomly-generated key
   *
   * @param string $url Long URL
   * @return array Data array
   */
  public function insert_long_url($url) {
    $firstKey_bin = $this->randbytes(8);
    $secondKey_bin = $this->randbytes(8);
    $key = $firstKey_bin . $secondKey_bin;

    $valid_protocol = false;
    foreach($this->allowed_protocols as $proto) {
      if(strtolower(substr($url, 0, strlen($proto) + 1)) == strtolower("$proto:")) {
        $valid_protocol = true;
        break;
      }
    }
    if(!$valid_protocol) {
      throw new M29Exception(array(
        'reason' => 'invalid',
        'message' => 'Invalid URL protocol',
        'locationType' => 'parameter',
        'location' => 'longUrl'
      ));
    }

    $longUrlEncrypted_bin = $this->encrypt($url, $key);
    return($this->insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin));
  }

  /**
   * Shorten an encrypted long URL
   *
   * @param string $longUrlEncrypted_bin Encrypted URL (non-Base64)
   * @param string $firstKey_bin First half of key (non-Base64)
   * @param string $secondKey_bin Optional second half of key (non-Base64)
   * @return array Data array
   */
  public function insert_encrypted_url($longUrlEncrypted_bin, $firstKey_bin, $secondKey_bin = '') {
    if(!(strlen($firstKey_bin) == 8)) {
      throw new M29Exception(array(
        'reason' => 'invalid',
        'message' => 'firstKey must be 64 bits (8 bytes)',
        'locationType' => 'parameter',
        'location' => 'firstKey'
      ));
    }
    if(strlen($longUrlEncrypted_bin) > $this->max_url_length) {
      throw new M29Exception(array(
        'reason' => 'invalid',
        'message' => 'URLs must be ' . $this->max_url_length . ' characters or less',
        'locationType' => 'parameter',
        'location' => 'longUrl'
      ));
    }
    if($secondKey_bin) {
      if(!(strlen($secondKey_bin) == 8)) {
        throw new M29Exception(array(
          'reason' => 'invalid',
          'message' => 'secondKey must be 64 bits (8 bytes)',
          'locationType' => 'parameter',
          'location' => 'secondKey'
        ));
      }
      $key = $firstKey_bin . $secondKey_bin;
      $url = $this->decrypt($longUrlEncrypted_bin, $key);
      $url = str_replace(chr(0), '', $url);

      $valid_protocol = false;
      foreach($this->allowed_protocols as $proto) {
        if(strtolower(substr($url, 0, strlen($proto) + 1)) == strtolower("$proto:")) {
          $valid_protocol = true;
          break;
        }
      }
      if(!$valid_protocol) {
        throw new M29Exception(array(
          'reason' => 'invalid',
          'message' => 'Invalid decryption keys or URL protocol',
          'locationType' => 'parameter',
          'location' => 'longUrl'
        ));
      }
    }

    $now = time();
    try {
      $this->db_connect();
      $sth = $this->dbh->prepare("insert into urls (created_at, hits, encrypted_url, first_key) values (?, 0, ?, ?)");
      $sth->bindValue(1, $now, PDO::PARAM_INT);
      $sth->bindValue(2, $longUrlEncrypted_bin, PDO::PARAM_STR);
      $sth->bindValue(3, $firstKey_bin, PDO::PARAM_STR);
      $sth->execute();
    } catch(PDOException $e) {
      throw new M29Exception(array(
        'reason' => 'serviceError',
        'message' => 'Database error: ' . $e->getMessage()
      ));
    }

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
      'short_url_incomplete' => $short_url_incomplete
    );
    if($secondKey_bin) {
      $out['long_url'] = $url;
    }
    return($out);
  }

  /**
   * Retrieve information about a short URL
   *
   * @param string $shortUrl Full short URL
   * @param bool $increment_hits Whether to increment the hit count on
   *                             the short URL statistics
   * @return array Data array
   */
  public function process_short_url($shortUrl, $increment_hits = false) {
    if(!substr($shortUrl, 0, strlen($this->base_url)) == $this->base_url) {
      throw new M29Exception(array(
        'reason' => 'notFound',
        'message' => 'Not Found',
        'locationType' => 'parameter',
        'location' => 'shortUrl'
      ));
    }
    if(!preg_match('/^\/([A-Za-z0-9\_\-]+)\/([A-Za-z0-9\_\-]+)$/', substr($shortUrl, strlen($this->base_url)), $m)) {
      throw new M29Exception(array(
        'reason' => 'notFound',
        'message' => 'Not Found',
        'locationType' => 'parameter',
        'location' => 'shortUrl'
      ));
    }
    $id_bin = $this->base64_decode_url($m[1]);
    $secondKey_bin = $this->base64_decode_url($m[2]);

    if(!(strlen($secondKey_bin) == 8)) {
      throw new M29Exception(array(
        'reason' => 'notFound',
        'message' => 'Not Found',
        'locationType' => 'parameter',
        'location' => 'shortUrl'
      ));
    }

    try {
      $this->db_connect();
      $sth = $this->dbh->prepare("select * from urls where id = ?");
      $sth->bindValue(1, $this->chars2int($id_bin), PDO::PARAM_INT);
      $sth->execute();
    } catch(PDOException $e) {
      throw new M29Exception(array(
        'reason' => 'serviceError',
        'message' => 'Database error: ' . $e->getMessage()
      ));
    }

    $row = array();
    $rowsfound = 0;
    while($rowi = $sth->fetch()) {
      $row = $rowi;
      $rowsfound++;
    }
    if($rowsfound == 0) {
      throw new M29Exception(array(
        'reason' => 'notFound',
        'message' => 'Not Found',
        'locationType' => 'parameter',
        'location' => 'shortUrl'
      ));
    }
    $firstKey_bin = $row['first_key'];
    $longUrlEncrypted_bin_check = $row['encrypted_url'];
    $key = $firstKey_bin . $secondKey_bin;
    $longUrl = $this->decrypt($longUrlEncrypted_bin_check, $key);
    $longUrl = str_replace(chr(0), '', $longUrl);

    $valid_protocol = false;
    foreach($this->allowed_protocols as $proto) {
      if(strtolower(substr($longUrl, 0, strlen($proto) + 1)) == strtolower("$proto:")) {
        $valid_protocol = true;
        break;
      }
    }
    if(!$valid_protocol) {
      throw new M29Exception(array(
        'reason' => 'invalid',
        'message' => 'Invalid decryption keys or URL protocol',
        'locationType' => 'parameter',
        'location' => 'longUrl'
      ));
    }
    if(preg_match('/[\r\n]/', $longUrl)) {
      throw new M29Exception(array(
        'reason' => 'invalid',
        'message' => 'Invalid URL component',
        'locationType' => 'parameter',
        'location' => 'longUrl'
      ));
    }

    $out = array(
      'long_url' => $longUrl,
      'created_at' => $row['created_at'],
      'accessed_at' => $row['accessed_at'],
      'hits' => $row['hits']
    );

    if($increment_hits) {
      $now = time();
      try {
        $sth = $this->dbh->prepare("update urls set accessed_at = ?, hits = hits + 1 where id = ?");
        $sth->bindValue(1, $now, PDO::PARAM_INT);
        $sth->bindValue(2, $this->chars2int($id_bin), PDO::PARAM_INT);
        $sth->execute();
      } catch(PDOException $e) {
        throw new M29Exception(array(
          'reason' => 'serviceError',
          'message' => 'Database error: ' . $e->getMessage()
        ));
      }
    }

    return($out);
  }

}

class M29Exception extends Exception {
  public $error = array();

  public function __construct($error) {
    $this->error = $error;
    parent::__construct($error['message'], 0, null);
  }
}
