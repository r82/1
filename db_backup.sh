#!/bin/bash

#==============================================================================

# example usage:
# INSECURE way
#  bash db_backup.sh --db_user root --db_pass my_insecure_WARNING_pass --db_name my_db_name --backup_dst sql_backup_folder
# SECURE way
#  bash db_backup.sh --sqlpwd_file .sqlpwd --db_name my_db_name --backup_dst sql_backup_folder

# http://stackoverflow.com/questions/9293042/mysqldump-without-the-password-prompt
# http://serverfault.com/questions/476228/whats-a-secure-alternative-to-using-a-mysql-password-on-the-command-line

#==============================================================================



# http://www.bahmanm.com/blogs/command-line-options-how-to-parse-in-bash-using-getopt
if [ $(($#%2)) -ne 0 ]; then
	>&2 echo "error: uneven number of arguments"
fi
declare -A args
count=0
last_arg=""
for var in "$@"
do
	if [ $((count%2)) -eq 0 ]; then
    if [ ${var:0:2} != "--" ]; then
    	>&2 echo "error: wrong argument name  "$var
    fi
    last_arg=${var:2}
	fi
	if [ $((count%2)) -ne 0 ]; then
		args[$last_arg]=$var
	fi
  (( count++ ))
done


db_host="localhost"
if [ ${args[db_host]+x} ]; then
	db_host=${args[db_host]}
fi

if [ ${args[db_user]+x} ]; then
	db_user=${args[db_user]}
fi

if [ ${args[db_pass]+x} ]; then
	db_pass=${args[db_pass]}
fi

if [ ${args[db_name]+x} ]; then
	db_name=${args[db_name]}
fi

if [ ${args[sqlpwd_file]+x} ]; then
	sqlpwd_file=${args[sqlpwd_file]}
fi

backup_dst="./"
if [ ${args[backup_dst]+x} ]; then
	backup_dst=${args[backup_dst]}
fi
if [ ${backup_dst:0:1} != "/" ]; then
	backup_dst=$PWD"/"$backup_dst
fi
if [ ! -d "$backup_dst" ]; then
  mkdir -p "$backup_dst"
fi
# backup_dst=$(realpath -e $backup_dst) # some servers deny permisson
backup_dst=${backup_dst%/}/
echo "backup_dst: $backup_dst"

backup_date_format="%Y-%m-%d_%H%M%S"
if [ ${args[backup_date_format]+x} ]; then
	backup_date_format=${args[backup_date_format]}
fi

backup_file_name=$backup_dst"sqlbackup_"$db_name"_"$(date +$backup_date_format).sql.gz
backup_file_name_UNFINISHED=$backup_file_name".UNFINISHED"

# http://stackoverflow.com/questions/18096670/what-does-z-mean-in-bash
if [[ $db_user ]]  && [[ $db_pass ]]; then
 mysqldump --user="$db_user" --password="$db_pass" -h "$db_host" "$db_name" | gzip -v > $backup_file_name_UNFINISHED
elif [[ $sqlpwd_file ]]; then
 	mysqldump --defaults-extra-file="$sqlpwd_file" -h "$db_host" "$db_name" | gzip -v > $backup_file_name_UNFINISHED
fi

mv "$backup_file_name_UNFINISHED" "$backup_file_name"