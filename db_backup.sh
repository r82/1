#!/bin/bash

#==============================================================================

# example usage:
# INSECURE way
#  bash db_backup.sh --db_user root --db_pass my_insecure_WARNING_pass --db_name my_db_name --backup_dst __BACKUP
# SECURE way
#  bash db_backup.sh --sqlpwd_file .sqlpwd --db_name my_db_name --backup_dst __BACKUP

# http://stackoverflow.com/questions/9293042/mysqldump-without-the-password-prompt
# http://serverfault.com/questions/476228/whats-a-secure-alternative-to-using-a-mysql-password-on-the-command-line

# ver. 2016-05-25 16:32:24

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
if [[ $db_name =~ [\"\'\;] ]]; then
  >&2 echo "wrong db_name: $db_name"
  exit
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
cd "$backup_dst"
backup_dst=$PWD/
cd - > /dev/null
# backup_dst=$(realpath -e $backup_dst) # some servers deny permisson

backup_date_format="%Y-%m-%d_%H%M%S"
if [ ${args[backup_date_format]+x} ]; then
	backup_date_format=${args[backup_date_format]}
fi

backup_prefix="mysqldump"
if [ ${args[backup_prefix]+x} ]; then
  backup_prefix=${args[backup_prefix]}
fi

backup_file_name=$backup_dst$backup_prefix"__"$db_name"__"$(date +$backup_date_format).sql.gz
backup_file_name_UNFINISHED=$backup_file_name".UNFINISHED"

# printf "\n"

time_start=$(date +%s%N)
if [[ $db_user ]]  && [[ $db_pass ]]; then
  mysqldump --user="$db_user" --password="$db_pass" -h "$db_host" "$db_name" | gzip -v > $backup_file_name_UNFINISHED
elif [[ $sqlpwd_file ]]; then
  mysql --defaults-extra-file="$sqlpwd_file" -h "$db_host" -e "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, CREATE_TIME, UPDATE_TIME, TABLE_COMMENT FROM information_schema.TABLES WHERE table_schema = '$db_name';"
  mysqldump --defaults-extra-file="$sqlpwd_file" -h "$db_host" "$db_name" | gzip -v > $backup_file_name_UNFINISHED
fi
time_end=$(date +%s%N)
time_diff=$((time_end-time_start))

mv "$backup_file_name_UNFINISHED" "$backup_file_name"

echo " ========================================"
echo -e "\e[01;37m"" backup_src:  \e[01;33m"$db_host/$db_name"\e[0m"
echo -e "\e[01;37m"" backup_dst:  \e[01;33m"$backup_file_name"\e[0m"
# https://stackoverflow.com/questions/1815329/portable-way-to-get-file-size-in-bytes-in-shell
echo -e "\e[01;37m"" backup_size: \e[01;33m"$(du -h "$backup_file_name" | cut -f1 )"  ( "$(wc -c < "$backup_file_name" )" )""\e[0m"
# http://www.cyberciti.biz/faq/linux-unix-formatting-dates-for-display/
echo -e "\e[01;37m"" time_start:  \e[01;33m"$(date -d "1970-01-01 UTC + ${time_start: : -9} seconds" +'%Y-%m-%d %H:%M:%S %:z')"  ${time_start: : -9}."${time_start: -9}"\e[0m"
echo -e "\e[01;37m"" time_end:    \e[01;33m"$(date -d "1970-01-01 UTC + ${time_end: : -9} seconds" +'%Y-%m-%d %H:%M:%S %:z')"  ${time_end: : -9}."${time_end: -9}"\e[0m"
echo -e "\e[01;37m"" time_diff:   \e[01;33m"$((time_diff/1000000000))"."$((time_diff%1000000000))"\e[0m"