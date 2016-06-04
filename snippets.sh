# backup
find -type f | egrep -i "[.](php|js|css|htm|html|xml|xls)$" | tar -czvf backup$(date +"%Y-%m-%d_%H%M%S").tar.gz -T -
# -l - list only files
grep -rIl '.' | tar -czvf backup$(date +"%Y-%m-%d_%H%M%S").tar.gz -T -

# find string ( I - ignore binary )
grep --color -rI -F -o 'string-to-find'
grep --color --include='*.*' -F -o 'string-to-find'


# http://unix.stackexchange.com/questions/20804/in-a-regular-expression-which-characters-need-escaping
find . -regextype sed -regex '.*/.*[.]\(jpg\|png\|gif\)$'
find . -regextype sed -regex 'cron'

# http://www.cyberciti.biz/faq/where-is-the-crontab-file/
# https://www.maketecheasier.com/cron-alternatives-linux/

# http://superuser.com/questions/11008/how-do-i-find-out-what-version-of-linux-im-running
uname -a
cat /etc/*{release,version,issue}


# https://stackoverflow.com/questions/2793812/generate-a-random-filename-in-unix-shell
temp_file_name=$(cat /dev/urandom | tr -dc 'a-z0-9' | fold -w 32 | head -n 1)

# https://askubuntu.com/questions/61179/find-the-latest-file-by-modified-date
find . -printf '%T+ %p\n' | sort -r | head