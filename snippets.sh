# backup
find -type f | egrep -i "[.]php|js|css|htm|html|xml|xls$" | tar -czvf backup$(date +"%Y-%m-%d_%H;%M;%S").tar.gz -T -