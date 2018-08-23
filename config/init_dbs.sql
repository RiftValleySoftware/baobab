CREATE USER littlegr_badg;
SET PASSWORD FOR littlegr_badg = 'pnpbxI1aU0L(';

CREATE DATABASE littlegr_badger_security;

use littlegr_badger_security;
CREATE TABLE co_security_nodes (
  id bigint(20) UNSIGNED NOT NULL,
  api_key varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  login_id varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  access_class varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  last_access datetime NOT NULL,
  read_security_id bigint(20) DEFAULT NULL,
  write_security_id bigint(20) DEFAULT NULL,
  object_name varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  access_class_context mediumtext,
  ids varchar(4095) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

GRANT ALL PRIVILEGES ON littlegr_badger_security.* TO 'littlegr_badg';

INSERT INTO co_security_nodes (id, api_key, login_id, access_class, last_access, read_security_id, write_security_id, object_name, access_class_context, ids) VALUES
(1, NULL, NULL, 'CO_Security_Node', '1970-01-01 00:00:00', -1, -1, NULL, NULL, NULL),
(2, NULL, 'admin', 'CO_Security_Login', '1970-01-01 00:00:00', 2, 2, 'God Admin Login', 'a:2:{s:4:"lang";s:2:"en";s:15:\"hashed_password\";s:60:\"$2y$10$XpzX3po3WSjRN98Zq.3Z3ev5nkgiz7GKsDo70zULYbJRWHhlwbbRC\";}', NULL);
  
CREATE DATABASE littlegr_badger_data;

use littlegr_badger_data;
CREATE TABLE co_data_nodes (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  access_class varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  last_access datetime NOT NULL,
  read_security_id bigint(20) DEFAULT NULL,
  write_security_id bigint(20) DEFAULT NULL,
  object_name varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  access_class_context blob,
  owner bigint(20) UNSIGNED DEFAULT NULL,
  longitude double DEFAULT NULL,
  latitude double DEFAULT NULL,
  tag0 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag1 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag2 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag3 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag4 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag5 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag6 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag7 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag8 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  tag9 varchar(255) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  payload longblob,
  PRIMARY KEY (id),
  KEY access_class (access_class),
  KEY last_access (last_access),
  KEY write_security_id (write_security_id),
  KEY read_security_id (read_security_id),
  KEY object_name (object_name),
  KEY owner (owner),
  KEY longitude (longitude),
  KEY latitude (latitude),
  KEY tag0 (tag0),
  KEY tag1 (tag1),
  KEY tag2 (tag2),
  KEY tag3 (tag3),
  KEY tag4 (tag4),
  KEY tag5 (tag5),
  KEY tag6 (tag6),
  KEY tag7 (tag7),
  KEY tag8 (tag8),
  KEY tag9 (tag9)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

GRANT ALL PRIVILEGES ON littlegr_badger_data.* TO 'littlegr_badg';

INSERT INTO co_data_nodes (id, access_class, last_access, read_security_id, write_security_id, object_name, access_class_context, owner, longitude, latitude, tag0, tag1, tag2, tag3, tag4, tag5, tag6, tag7, tag8, tag9, payload) VALUES
(1, 'CO_Main_DB_Record', '1970-01-02 00:00:00', -1, -1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
