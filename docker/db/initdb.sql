USE mysql;
CREATE USER 'replicator'@'%' IDENTIFIED BY 'repl1234or';
GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';
-- # do note that the replicator permission cannot be granted on single database.
FLUSH PRIVILEGES;
SHOW MASTER STATUS;
SHOW VARIABLES LIKE 'server_id';

-- stop slave;
-- CHANGE MASTER TO MASTER_HOST = 'replica',
--     MASTER_USER = 'replicator',
--     MASTER_PASSWORD = 'repl1234or',
--     MASTER_LOG_FILE = 'mysql-bin.000003',
-- MASTER_LOG_POS = 154;
-- start slave;
-- show slave status;