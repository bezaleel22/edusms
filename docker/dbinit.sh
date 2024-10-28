#!/bin/bash 
set -e

check() {
    STATUS1=$(mysqladmin ping -h $LOCAL_HOST -u root --password=$ROOT_PASSWORD)
    STATUS2=$(mysqladmin ping -h $REMOTE_HOST -u root --password=$ROOT_PASSWORD)

    if [ "$STATUS1" = "mysqld is alive" ]; then
        if [ "$STATUS2" = "mysqld is alive" ]; then
            return 0
        fi
    fi

    return 1
}

master_master() {

    echo
    echo Connecting replicas...
    echo

    export REPL_USER=${MYSQL_REPLICATION_USER:-'replicator'}
    export REPL_PASSWORD=${MYSQL_REPLICATION_PASSWORD:-'repl1234or'}
    export ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-'14w0rdlla'}
    export REMOTE_HOST=${MYSQL_REMOTE_HOST:-'remotedb'}
    export LOCAL_HOST=${MYSQL_LOCAL_HOST:-'localdb'}
    

    until check; do
    echo "Waiting for replicas to be ready"
    sleep 5
    done

    echo "Replicas are ready"
   
    echo "Setting up RemoteDB"
    # Get the log position and name.
    master1=$(mysql -u root --password=$ROOT_PASSWORD -h $REMOTE_HOST --execute="show master status;")
    master1_log=$(echo $master1|awk '{print $5}')
    master1_position=$(echo $master1|awk '{print $6}')

    # # # # Connect slave to master.
    mysql -u root --password=$ROOT_PASSWORD -h $REMOTE_HOST --execute="stop slave;\
        reset slave;\
        CHANGE MASTER TO MASTER_HOST='$LOCAL_HOST', MASTER_USER='$REPL_USER', \
        MASTER_PASSWORD='$REPL_PASSWORD', MASTER_LOG_FILE='$master1_log', MASTER_LOG_POS=$master1_position; \
        start slave;\
        SHOW SLAVE STATUS\G;"

    echo "Setting up LocalDB"
  # Get the log position and name.
    master2=$(mysql -u root --password=$ROOT_PASSWORD -h $LOCAL_HOST --execute="show master status;")
    master2_log=$(echo $master2|awk '{print $5}')
    master2_position=$(echo $master2|awk '{print $6}')

    # # # # Connect slave to master.
    mysql -u root --password=$ROOT_PASSWORD -h $LOCAL_HOST --execute="stop slave;\
        reset slave;\
        CHANGE MASTER TO MASTER_HOST='$REMOTE_HOST', MASTER_USER='$REPL_USER', \
        MASTER_PASSWORD='$REPL_PASSWORD', MASTER_LOG_FILE='$master2_log', MASTER_LOG_POS=$master2_position; \
        start slave;\
        SHOW SLAVE STATUS\G;"

	sleep 2
	echo
	echo ###################	SECOND status

    mysql -u root --password=$ROOT_PASSWORD -h $REMOTE_HOST --execute="show master status;"

	sleep 2
	echo
	echo ###################	FIRST status

    mysql -u root --password=$ROOT_PASSWORD -h $LOCAL_HOST --execute="show master status;"
}

master_master
