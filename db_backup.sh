#!/bin/bash

# http://stackoverflow.com/questions/9293042/mysqldump-without-the-password-prompt
# http://serverfault.com/questions/476228/whats-a-secure-alternative-to-using-a-mysql-password-on-the-command-line

if [ ! -z ${1+x} ]; then
	db_name=$3
fi

backup_date_format="%Y-%m-%d_%H%M%S"

mysqldump --user="$1" --password="$2" -h localhost "$db_name" > "sqlbackup_"$db_name"_"$(date +$backup_date_format).sql