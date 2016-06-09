#!/bin/bash

#==============================================================================
# example usage:
# bash folder_backup.sh --backup_src public_html --backup_dst __BACKUP
# ver. 2016-05-25 16:32:36
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

# for i in "${!args[@]}"
# do
#   echo "key  : $i"
#   echo "value: ${args[$i]}"
# done

# echo "BASH_VERSION: $BASH_VERSION"

# http://stackoverflow.com/questions/3601515/how-to-check-if-a-variable-is-set-in-bash
# http://stackoverflow.com/questions/13219634/easiest-way-to-check-for-an-index-or-a-key-in-an-array


backup_src="."
if [ ${args[backup_src]+x} ]; then
	backup_src=${args[backup_src]}
fi
backup_src=${backup_src%/}/
if [ ! -d "$backup_src" ]; then
  >&2 echo "no backup source: $backup_src"
  exit
fi
cd "$backup_src"
backup_src=$PWD/
cd - > /dev/null
# backup_src="$(cd "$(dirname "$backup_src")"; pwd)/$(basename "$backup_src")"
# backup_src=${backup_src%/}/

# https://superuser.com/questions/146754/on-linux-unix-does-tar-gz-versus-zip-matter
out_format="zip"
if [ ${args[out_format]+x} ]; then
  out_format=${args[backup_dst]}
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

# https://stackoverflow.com/questions/3915040/bash-fish-command-to-print-absolute-path-to-a-file
# backup_dst=$(realpath -e "$backup_dst")
# backup_dst="$(cd "$(dirname "$backup_dst")"; pwd)/$(basename "$backup_dst")"
# backup_dst=${backup_dst%/}/
cd "$backup_dst"
backup_dst=$PWD/
cd - > /dev/null

# http://www.file-extensions.org/filetype/extension/name/text-files
backup_regex=".*"
if [ ${args[backup_regex]+x} ]; then
	backup_regex=${args[backup_regex]}
fi

if [ "$backup_regex" = "<predefined_text_exts>" ]; then
	backup_regex="[.](php|js|css|htm|html|xml|xls|[.]htaccess|log|svg|sh|txt|sql|md|json|ini|inf|operations|tmpl|po|pot|mo|less|[.]editorconfig|crt|ts|[.]gitignore|dist|htc|yml)$"
fi

backup_date_format="%Y-%m-%d_%H%M%S"
if [ ${args[backup_date_format]+x} ]; then
	backup_date_format=${args[backup_date_format]}
fi


cd "$backup_src"
backup_name=${PWD##*/}

if [ "$out_format" = "targz" ]; then
  backup_file_ext="tar.gz"
elif [ "$out_format" = "zip" ]; then
  backup_file_ext="zip"
else
  >&2 echo "error: wrong out_format [zip|targz]  "$var
fi

backup_file_name=$backup_dst"backup__"$backup_name"__"$(date +$backup_date_format)"."$backup_file_ext
backup_file_name_UNFINISHED=$backup_file_name".UNFINISHED"

echo " backup_src: $backup_src"
echo " backup_dst: $backup_file_name"
echo "========================================"

time_start=$(date +%s%N)
if [ "$out_format" = "targz" ]; then
  if [ "$backup_regex" = "<guess_text_files_by_content>" ]; then
  	# it's mistakenly taking *.pdf files as text
  	find . -type f -exec grep --exclude="*.pdf" -Iq . {} \; -and -print | sort |  tar -czvf "$backup_file_name_UNFINISHED" -T -
  else
  	find . -type f | egrep -i "$backup_regex" | sort | tar -czvf "$backup_file_name_UNFINISHED" -T -
  fi
elif [ "$out_format" = "zip" ]; then
  mkdir "$backup_file_name_UNFINISHED"
  zip_opts=-9
  if [ "$backup_regex" = "<guess_text_files_by_content>" ]; then
    # it's mistakenly taking *.pdf files as text
    find . -type f -exec grep --exclude="*.pdf" -Iq . {} \; -and -print | sort | zip $zip_opts -b "$backup_file_name_UNFINISHED" "$backup_file_name" -@
  else
    find . -type f | egrep -i "$backup_regex" | sort | zip $zip_opts -b "$backup_file_name_UNFINISHED" "$backup_file_name" -@
  fi
else
  >&2 echo "error: wrong out_format [zip|targz]  "$var
fi
time_end=$(date +%s%N)
time_diff=$((time_end-time_start))

if [ -f "$backup_file_name_UNFINISHED" ]; then
  mv "$backup_file_name_UNFINISHED" "$backup_file_name"
elif [ -d "$backup_file_name_UNFINISHED" ]; then
  rmdir "$backup_file_name_UNFINISHED"
fi

echo " ========================================"
echo -e "\e[01;37m"" backup_src:  \e[01;33m"$backup_src"\e[0m"
echo -e "\e[01;37m"" backup_dst:  \e[01;33m"$backup_file_name"\e[0m"
# https://stackoverflow.com/questions/1815329/portable-way-to-get-file-size-in-bytes-in-shell
echo -e "\e[01;37m"" backup_size: \e[01;33m"$(du -h "$backup_file_name" | cut -f1 )"  ( "$(wc -c < "$backup_file_name" )" )""\e[0m"
# http://www.cyberciti.biz/faq/linux-unix-formatting-dates-for-display/
echo -e "\e[01;37m"" time_start:  \e[01;33m"$(date -d "1970-01-01 UTC + ${time_start: : -9} seconds" +'%Y-%m-%d %H:%M:%S %:z')"  ${time_start: : -9}."${time_start: -9}"\e[0m"
echo -e "\e[01;37m"" time_end:    \e[01;33m"$(date -d "1970-01-01 UTC + ${time_end: : -9} seconds" +'%Y-%m-%d %H:%M:%S %:z')"  ${time_end: : -9}."${time_end: -9}"\e[0m"
echo -e "\e[01;37m"" time_diff:   \e[01;33m"$((time_diff/1000000000))"."$((time_diff%1000000000))"\e[0m"