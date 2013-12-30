#/bin/bash
cd /var/www/spider
php -f smzdm.start.php start
cd tools
php -f smzdm.php
cd /var/www/sale_info/
zip all.zip data img -r
cd -
php -f ftp_upload.php /var/www/sale_info/all.zip /public_html/
curl http://www.eccbuy.net/zip.php?file=all.zip
