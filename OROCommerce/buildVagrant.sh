#!/usr/bin/env bash

#run as root

echo "proxy=http://10.10.106.113:3128" >> /etc/yum.conf

#FORWARDED_PORT=8000

# --- Database settings ---
DB_USER="postgres"
DB_PASSWORD="postgres"
DB_NAME="oro"

echo "$DB_USER" > /tmp/oro_install_DB_USER
echo "$DB_PASSWORD" > /tmp/oro_install_DB_PASSWORD
echo "$DB_NAME" > /tmp/oro_install_DB_NAME

# --- RabbitMQ settings ---
RABBITMQ_USER="rbmquser"
RABBITMQ_PASSWORD="rbmqpassword"

echo "$RABBITMQ_USER" > /tmp/oro_install_RABBITMQ_USER
echo "$RABBITMQ_PASSWORD" > /tmp/oro_install_RABBITMQ_PASSWORD

# --- Oro application settings ---
APP_HOST="oro.local"
APP_USER="admin"
APP_PASSWORD="Admin123abcABC"
APP_LOAD_DEMO_DATA="y"

echo "$APP_HOST" > /tmp/oro_install_APP_HOST
echo "$APP_USER" > /tmp/oro_install_APP_USER
echo "$APP_PASSWORD" > /tmp/oro_install_APP_PASSWORD
echo "$APP_LOAD_DEMO_DATA" > /tmp/oro_install_APP_LOAD_DEMO_DATA
chmod 777 /tmp/oro*

echo "~~~~~~~~~~~~~~ Enable Required Package Repositories ~~~~~~~~~~~~~~"
yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm yum-utils scl-utils centos-release-scl centos-release-scl-rh
yum-config-manager --add-repo http://koji.oro.cloud/rpms/oro-el7.repo
yum update -y

echo "~~~~~~~~~~~~~~ Install Wget, Unzip ~~~~~~~~~~~~~~"
yum install -y wget unzip telnet

echo "~~~~~~~~~~~~~~ Install Nginx, PostgreSQL, Redis, ElasticSearch, NodeJS, Git, Supervisor, and Wget ~~~~~~~~~~~~~~"
yum install -y rh-postgresql96 rh-postgresql96-postgresql rh-postgresql96-postgresql-server rh-postgresql96-postgresql-contrib rh-postgresql96-postgresql-syspaths oro-elasticsearch24 oro-elasticsearch24-runtime oro-elasticsearch24-elasticsearch oro-redis4 oro-redis4-runtime oro-redis4-redis oro-rabbitmq-server36 oro-rabbitmq-server36-runtime oro-rabbitmq-server36-rabbitmq-server nginx nodejs npm git bzip2 supervisor

echo "~~~~~~~~~~~~~~ Install PHP ~~~~~~~~~~~~~~"
yum install -y oro-php71 oro-php71-php-cli oro-php71-php-fpm oro-php71-php-opcache oro-php71-php-mbstring oro-php71-php-mcrypt oro-php71-php-pgsql oro-php71-php-process oro-php71-php-ldap oro-php71-php-gd oro-php71-php-intl oro-php71-php-bcmath oro-php71-php-xml oro-php71-php-soap oro-php71-php-tidy oro-php71-php-zip

echo "~~~~~~~~~~~~~~ Install Composer ~~~~~~~~~~~~~~"
wget http://172.17.3.91/project/rri/composerpacakge/composer
chmod 755 composer &&  mv composer /usr/bin/composer

echo "~~~~~~~~~~~~~~ Perform Security Configuration ~~~~~~~~~~~~~~"
sed -i 's/SELINUX=enforcing/SELINUX=permissive/g' /etc/selinux/config
setenforce permissive

echo "~~~~~~~~~~~~~~ Prepare PostgreSQL Database ~~~~~~~~~~~~~~"
# --- Initialize a PostgreSQL Database Cluster ---
scl enable rh-postgresql96 'postgresql-setup --initdb'

# --- Enable Password Protected PostgreSQL Authentication ---
sed -i 's/all[ ]*127.0.0.1\/32[ ]*ident/all   127.0.0.1\/32   md5/g' /var/opt/rh/rh-postgresql96/lib/pgsql/data/pg_hba.conf
sed -i 's/all[ ]*::1\/128[ ]*ident/all   ::1\/128   md5/g' /var/opt/rh/rh-postgresql96/lib/pgsql/data/pg_hba.conf

#allow remote access
echo "host all all 0.0.0.0/0 md5" >> /var/opt/rh/rh-postgresql96/lib/pgsql/data/pg_hba.conf
echo "listen_addresses = '*'" >> /var/opt/rh/rh-postgresql96/lib/pgsql/data/postgresql.conf

# --- Change the Password for the postgres User ---
systemctl start rh-postgresql96-postgresql
su - postgres -c "psql -U postgres -c \"alter user $DB_USER with password '$DB_PASSWORD';\""

# --- Create a Database for OroCommerce Enterprise Edition Application ---
su - postgres -c "psql -U postgres -c \"CREATE DATABASE $DB_NAME;\""
su - postgres -c "psql -U postgres -d $DB_NAME -c 'CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";'"

echo "~~~~~~~~~~~~~~ Configure Web Server ~~~~~~~~~~~~~~"
sed -i 's/user nginx/user vagrant/g' /etc/nginx/nginx.conf

cat > /etc/nginx/conf.d/default.conf <<____NGINXCONFIGTEMPLATE
server {
server_name $APP_HOST www.$APP_HOST;
root  /opt/project/oroapp/public;

index index_dev.php;

gzip on;
gzip_proxied any;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
gzip_vary on;

location / {
# try to serve file directly, fallback to index.php
try_files \$uri /index.php\$is_args\$args;
}

location ~ ^/(index|index_dev|config|install)\.php(/|$) {
fastcgi_pass 127.0.0.1:9000;
# or
# fastcgi_pass unix:/var/run/php/php7-fpm.sock;
fastcgi_split_path_info ^(.+\.php)(/.*)$;
include fastcgi_params;
fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
fastcgi_param HTTPS off;
fastcgi_buffers 64 64k;
fastcgi_buffer_size 128k;
}

location ~* ^[^(\.php)]+\.(jpg|jpeg|gif|png|ico|css|pdf|ppt|txt|bmp|rtf|js)$ {
access_log off;
expires 1h;
add_header Cache-Control public;
}

error_log /var/log/nginx/${APP_HOST}_error.log;
access_log /var/log/nginx/${APP_HOST}_access.log;
}
____NGINXCONFIGTEMPLATE


echo "~~~~~~~~~~~~~~ Configure PHP ~~~~~~~~~~~~~~"
sed -i 's/user = apache/user = vagrant/g' /etc/opt/oro/oro-php71/php-fpm.d/www.conf
sed -i 's/group = apache/group = vagrant/g' /etc/opt/oro/oro-php71/php-fpm.d/www.conf
sed -i 's/;catch_workers_output/catch_workers_output/g' /etc/opt/oro/oro-php71/php-fpm.d/www.conf

sed -i 's/memory_limit = [0-9MG]*/memory_limit = 1G/g' /etc/opt/oro/oro-php71/php.ini
sed -i 's/;realpath_cache_size = [0-9MGk]*/realpath_cache_size = 4M/g' /etc/opt/oro/oro-php71/php.ini
sed -i 's/;realpath_cache_ttl = [0-9]*/realpath_cache_ttl = 600/g' /etc/opt/oro/oro-php71/php.ini

sed -i 's/opcache.enable=[0-1]/opcache.enable=1/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini
sed -i 's/;opcache.enable_cli=[0-1]/opcache.enable_cli=0/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini
sed -i 's/opcache.memory_consumption=[0-9]*/opcache.memory_consumption=512/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini
sed -i 's/opcache.interned_strings_buffer=[0-9]*/opcache.interned_strings_buffer=32/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini
sed -i 's/opcache.max_accelerated_files=[0-9]*/opcache.max_accelerated_files=32531/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini
sed -i 's/;opcache.save_comments=[0-1]/opcache.save_comments=1/g' /etc/opt/oro/oro-php71/php.d/10-opcache.ini

echo "~~~~~~~~~~~~~~ Configure RabbitMQ ~~~~~~~~~~~~~~"

# --- Create RabbitMQ User ---
systemctl start oro-rabbitmq-server36-rabbitmq-server
scl enable oro-rabbitmq-server36 'RABBITMQ_USER=$(cat /tmp/oro_install_RABBITMQ_USER) && RABBITMQ_PASSWORD=$(cat /tmp/oro_install_RABBITMQ_PASSWORD) && rabbitmqctl add_user $RABBITMQ_USER $RABBITMQ_PASSWORD'
scl enable oro-rabbitmq-server36 'RABBITMQ_USER=$(cat /tmp/oro_install_RABBITMQ_USER) && rabbitmqctl set_user_tags $RABBITMQ_USER administrator'
scl enable oro-rabbitmq-server36 'RABBITMQ_USER=$(cat /tmp/oro_install_RABBITMQ_USER) && rabbitmqctl set_permissions -p / $RABBITMQ_USER ".*" ".*" ".*"'

scl enable oro-rabbitmq-server36 'rabbitmqctl delete_user guest'

# --- Enable RabbitMQ Plugins ---
scl enable oro-rabbitmq-server36 'rabbitmq-plugins enable rabbitmq_delayed_message_exchange'
scl enable oro-rabbitmq-server36 'rabbitmq-plugins enable rabbitmq_management'

echo "~~~~~~~~~~~~~~ Enable Installed Services ~~~~~~~~~~~~~~"
systemctl restart rh-postgresql96-postgresql oro-rabbitmq-server36-rabbitmq-server oro-redis4-redis oro-elasticsearch24-elasticsearch oro-php71-php-fpm nginx supervisord
systemctl enable rh-postgresql96-postgresql oro-rabbitmq-server36-rabbitmq-server oro-redis4-redis oro-elasticsearch24-elasticsearch oro-php71-php-fpm nginx supervisord

echo "~~~~~~~~~~~~~~ Config SSH to allowed password authentication ~~~~~~~~~~~~~~"
sed -i '#PasswordAuthentication yes/PasswordAuthentication yes/g' /etc/ssh/sshd_config


echo "~~~~~~~~~~~~~~ Create other folders ~~~~~~~~~~~~~~"
mkdir -p /opt/project/orodata/cache
mkdir -p /opt/project/orodata/logs
mkdir -p /opt/project/orodata/vendor
chown -R vagrant.vagrant /opt/project

echo "alias seo='scl enable  oro-php71'" >> /home/vagrant/.bash_profile
