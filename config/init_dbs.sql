CREATE USER littlegr_badg;
SET PASSWORD FOR littlegr_badg = 'pnpbxI1aU0L(';

CREATE DATABASE littlegr_badger_data;
CREATE DATABASE littlegr_badger_security;

GRANT ALL PRIVILEGES ON DATABASE littlegr_badger_data TO littlegr_badg;
GRANT ALL PRIVILEGES ON DATABASE littlegr_badger_security TO littlegr_badg;

use littlegr_badger_security;
DROP TABLE IF EXISTS co_security_nodes;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO co_security_nodes (id, api_key, login_id, access_class, last_access, read_security_id, write_security_id, object_name, access_class_context, ids) VALUES
(1, NULL, NULL, 'CO_Security_Node', '1970-01-01 00:00:00', -1, -1, NULL, NULL, NULL),
(2, NULL, 'admin', 'CO_Security_Login', '1970-01-01 00:00:00', 2, 2, 'God Admin Login', 'a:2:{s:4:"lang";s:2:"en";s:15:\"hashed_password\";s:60:\"$2y$10$XpzX3po3WSjRN98Zq.3Z3ev5nkgiz7GKsDo70zULYbJRWHhlwbbRC\";}', NULL);

ALTER TABLE co_security_nodes
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY api_key (api_key),
  ADD UNIQUE KEY login_id (login_id),
  ADD KEY access_class (access_class),
  ADD KEY last_access (last_access),
  ADD KEY read_security_id (read_security_id),
  ADD KEY write_security_id (write_security_id),
  ADD KEY object_name (object_name);

ALTER TABLE co_security_nodes
  MODIFY id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
  
 use littlegr_badger_data;
 DROP TABLE IF EXISTS co_data_nodes;
DROP SEQUENCE IF EXISTS element_id_seq;
CREATE SEQUENCE element_id_seq;
CREATE TABLE co_data_nodes (
  id BIGINT NOT NULL DEFAULT nextval('element_id_seq'),
  access_class VARCHAR(255) NOT NULL,
  last_access TIMESTAMP NOT NULL,
  read_security_id BIGINT DEFAULT NULL,
  write_security_id BIGINT DEFAULT NULL,
  object_name VARCHAR(255) DEFAULT NULL,
  access_class_context TEXT DEFAULT NULL,
  owner BIGINT DEFAULT NULL,
  longitude DOUBLE PRECISION DEFAULT NULL,
  latitude DOUBLE PRECISION DEFAULT NULL,
  tag0 VARCHAR(255) DEFAULT NULL,
  tag1 VARCHAR(255) DEFAULT NULL,
  tag2 VARCHAR(255) DEFAULT NULL,
  tag3 VARCHAR(255) DEFAULT NULL,
  tag4 VARCHAR(255) DEFAULT NULL,
  tag5 VARCHAR(255) DEFAULT NULL,
  tag6 VARCHAR(255) DEFAULT NULL,
  tag7 VARCHAR(255) DEFAULT NULL,
  tag8 VARCHAR(255) DEFAULT NULL,
  tag9 VARCHAR(255) DEFAULT NULL,
  payload TEXT DEFAULT NULL
);

INSERT INTO co_data_nodes (access_class, last_access, read_security_id, write_security_id, object_name, access_class_context, owner, longitude, latitude, tag0, tag1, tag2, tag3, tag4, tag5, tag6, tag7, tag8, tag9, payload) VALUES
('CO_Main_DB_Record', '1970-01-02 00:00:00', -1, -1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
