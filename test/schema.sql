CREATE TABLE user (
  email VARCHAR(128) NOT NULL,
  crypt_password VARCHAR(128) NOT NULL,
  language VARCHAR(8) NOT NULL DEFAULT 'en_US',
  firstname VARCHAR(32) NOT NULL,
  lastname VARCHAR(32) NOT NULL,
  ticket VARCHAR(128),
  ticket_expiration_date INT DEFAULT 0 NOT NULL,
  PRIMARY KEY (email),
  KEY (crypt_password),
  KEY (ticket),
  KEY (ticket_expiration_date)
) ENGINE=MyISAM CHARSET=utf8;

CREATE TABLE password_recovery (
  email VARCHAR(128) NOT NULL,
  ticket VARCHAR(128),
  ticket_expiration_date INT DEFAULT 0 NOT NULL,
  PRIMARY KEY (email),
  KEY (ticket)
) ENGINE=MyISAM CHARSET=utf8;
