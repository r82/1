#!/bin/bash

# parameters
# 1 - destination folder
# 2 - source folder, files that we are going to save
# 3 - regex used to catch file names, if == <guess_text_files_by_content> then files that are text-only are stored
# 4 - date format
# all parameters are optional


# echo "BASH_VERSION: $BASH_VERSION"

# http://stackoverflow.com/questions/3601515/how-to-check-if-a-variable-is-set-in-bash
backup_src="."
if [ ! -z ${2+x} ]; then
	backup_src=$2
fi
backup_src=${backup_src%/}/
if [ ! -d "$backup_src" ]; then
  echo "no backup source: $backup_src";
  exit
fi

backup_dst="./"
if [ ! -z ${1+x} ]; then
	backup_dst=$1
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
if [ ! -z ${3+x} ]; then
	backup_regex=$3
fi

if [ "$backup_regex" = "<guess_text_files_by_ext>" ]; then
	backup_regex="[.](php|js|css|htm|html|xml|xls|[.]htaccess|log|svg|sh|txt|sql|md|json|ini|inf|operations|tmpl|po|pot|mo|less|[.]editorconfig|crt|ts|[.]gitignore|dist|htc|yml)$"
fi

backup_date_format="%Y-%m-%d_%H%M%S"
if [ ! -z ${4+x} ]; then
	backup_regex=$4
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