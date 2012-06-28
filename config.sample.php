<?php

# This statement is required.
$config = array();

# The base URL to construct short URLs.  This URL must not have the
# trailing slash.
#$config['base_url'] = 'http://yrste.xm';

# Database configuration.  These options are passed to PHP PDO, see
# http://php.net/manual/en/book.pdo.php for more information.  A few
# different examples are below.

# MySQL
#$config['pdo_dsn'] = 'mysql:host=localhost;dbname=m29';
#$config['pdo_username'] = 'm29';
#$config['pdo_password'] = 'yourpass';

# PostgreSQL
#$config['pdo_dsn'] = 'pgsql:host=localhost;dbname=m29;user=m29;password=yourpass';

# SQLite 3
# Note: Both the database file and the directory it's in must be
# writable by the web server.
#$config['pdo_dsn'] = 'sqlite:db/m29.sqlite';

# Maximum input URL length.  If you try to raise from the default 
# 2048, you may need to alter the database schema, depending on the DB 
# driver. Lowering from the default is safe.
#$config['max_url_length'] = 2048;

# Allowed protocols.  These are currently the default.  Note that due
# to the way M29 is architected, it's still possible for users to
# insert encrypted disallowed strings, but the redirect handler will
# catch them on the way out and forbid them.
#$config['allowed_protocols'] = array('http', 'https', 'ftp', 'gopher');

# Disable the API, if desired.
#$config['disable_api'] = false;
