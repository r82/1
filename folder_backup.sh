#!/bin/bash

#==============================================================================

# example usage:
# bash folder_backup.sh --backup_src public_html --backup_dst backups

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
		echo $var
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
  echo "no backup source: $backup_src";
  exit
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
backup_dst=$(realpath -e $backup_dst)
backup_dst=${backup_dst%/}/
echo "backup_dst: $backup_dst"
echo "backup_src: "$(realpath -e $backup_src)"/"

# http://www.file-extensions.org/filetype/extension/name/text-files
backup_regex=".*"
if [ ${args[backup_regex]+x} ]; then
	backup_regex=${args[backup_regex]}
fi

if [ "$backup_regex" = "<guess_text_files_by_ext>" ]; then
	backup_regex="[.](php|js|css|htm|html|xml|xls|[.]htaccess|log|svg|sh|txt|sql|md|json|ini|inf|operations|tmpl|po|pot|mo|less|[.]editorconfig|crt|ts|[.]gitignore|dist|htc|yml)$"
fi

backup_date_format="%Y-%m-%d_%H%M%S"
if [ ${args[backup_date_format]+x} ]; then
	backup_date_format=${args[backup_date_format]}
fi

backup_name=${PWD##*/}

cd $backup_src

backup_file_name=$backup_dst"backup_"$backup_name"_"$(date +$backup_date_format).tar.gz
backup_file_name_UNFINISHED=$backup_file_name".UNFINISHED"

if [ "$backup_regex" = "<guess_text_files_by_content>" ]; then
	# it's mistakenly taking *.pdf files as text
	find . -type f -exec grep --exclude="*.pdf" -Iq . {} \; -and -print | tar -czvf "$backup_file_name_UNFINISHED" -T -
else
	find . -type f | egrep -i "$backup_regex" | tar -czvf "$backup_file_name_UNFINISHED" -T -
fi

mv "$backup_file_name_UNFINISHED" "$backup_file_name"