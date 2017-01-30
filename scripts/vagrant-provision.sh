#!/bin/bash

if [ ! -d "/srv/state" ]; then
    mkdir /srv/state
fi

# Update everything in the instance and install required packages.
if [ ! -f "/srv/state/yum-packages" ]; then
    yum -y install epel-release
    yum -y install nginx
    yum -y install mariadb-server mariadb
    yum -y install php php-mysql php-fpm php-gd
    yum -y install vim

    touch "/srv/state/yum-packages"
fi

# wp-cli setup.
if [ ! -f "/usr/bin/wp" ]; then
    wget --quiet https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod a+x wp-cli.phar
    mv wp-cli.phar /usr/bin/wp
fi

if [ ! -f "/srv/state/server-config" ]; then
    sed -i s'/sendfile[ \t]*on/sendfile off/' /etc/nginx/nginx.conf
    sed -i s'/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php.ini
    sed -i s'/memory_limit = 128M/memory_limit = 512M/' /etc/php.ini
    sed -i s'/upload_max_filesize = 2M/upload_max_filesize = 50M/' /etc/php.ini
    sed -i s'/post_max_size = 8M/post_max_size = 50M/' /etc/php.ini
    sed -i s'/listen = 127.0.0.1:9000/listen = \/var\/run\/php-fpm\/php-fpm.sock/' /etc/php-fpm.d/www.conf
    sed -i s'/;listen.owner = nobody/listen.owner = nginx/' /etc/php-fpm.d/www.conf
    sed -i s'/;listen.group = nobody/listen.group = nginx/' /etc/php-fpm.d/www.conf
    sed -i s'/user = apache/user = nginx/' /etc/php-fpm.d/www.conf
    sed -i s'/group = apache/group = nginx/' /etc/php-fpm.d/www.conf

    usermod -a -G nginx vagrant

    systemctl enable nginx
    systemctl enable mariadb
    systemctl enable php-fpm

    systemctl start mariadb
    sleep 5
    mysqladmin -u root password root
    systemctl stop mariadb
    sleep 5

    touch "/srv/state/server-config"
fi

# if [ ! -d /srv/www ]; then
#     mkdir /srv/www
#     chown nginx.nginx /srv/www
# fi

# Site config.

if [ ! -f "/etc/nginx/conf.d/another-unit-converter.conf" ]; then
    cat <<"NGINXCONF" > /etc/nginx/conf.d/another-unit-converter.conf
server {
    listen 80;
    server_name another-unit-converter.dev;
    root /srv/www/;
    access_log /srv/www/access.log;
    error_log /srv/www/error.log error;
    client_max_body_size 100M;

    index index.php index.html;

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }

    if ( !-e $request_filename) {
        rewrite ^(.+)$ /index.php?q=$1 last;
    }

    # catch all
    error_page 404 /index.php;
}
NGINXCONF
fi

# MySQL database.
systemctl start mariadb
sleep 5
mysql -u root --password=root -e "CREATE DATABASE IF NOT EXISTS another_unit_converter"
mysql -u root --password=root -e "GRANT ALL PRIVILEGES ON another_unit_converter.* TO 'root'@'%' IDENTIFIED BY 'root';"

# Change to WP install directory.
cd /srv/www

# Install WP.
if ! $(wp core is-installed); then
    wp core download
    wp core config --dbname="another_unit_converter" --dbuser=root --dbpass=root --dbhost="localhost" --extra-php <<PHP
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'SAVEQUERIES', true );
PHP
    wp core install --url="http://another-unit-converter.dev" --title="Another Unit Converter Dev" --admin_user="admin" --admin_password="admin"  --admin_email="admin@another-unit-converter.dev"
fi

# Configure WP.
if [ -e /srv/www/wp-content/plugins/hello.php ]; then rm -f /srv/www/wp-content/plugins/hello.php; fi
if [ -e /srv/www/wp-content/plugins/akismet ]; then rm -rf /srv/www/wp-content/plugins/akismet; fi
if [ ! -e /srv/www/wp-content/themes/twentytwelve ]; then wp theme install twentytwelve --activate; fi
if [ ! -e /srv/www/wp-content/plugins/query-monitor ]; then wp plugin install query-monitor; fi

# Symlink plugins and themes.
if [ ! -e /srv/www/wp-content/plugins/another-unit-converter ]; then
    ln -s /vagrant/another-unit-converter /srv/www/wp-content/plugins/another-unit-converter;
fi

# Start server.
systemctl start php-fpm
systemctl start nginx
