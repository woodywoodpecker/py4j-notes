#!/usr/bin/env bash

#run as vagrant

FORWARDED_PORT=8000


# --- Database settings ---
DB_USER=$(cat /tmp/oro_install_DB_USER)
DB_PASSWORD=$(cat /tmp/oro_install_DB_PASSWORD)
DB_NAME=$(cat /tmp/oro_install_DB_NAME)

# --- RabbitMQ settings ---
RABBITMQ_USER=$(cat /tmp/oro_install_RABBITMQ_USER)
RABBITMQ_PASSWORD=$(cat /tmp/oro_install_RABBITMQ_PASSWORD)

APP_HOST=$(cat /tmp/oro_install_APP_HOST)
APP_USER=$(cat /tmp/oro_install_APP_USER)
APP_PASSWORD=$(cat /tmp/oro_install_APP_PASSWORD)
APP_LOAD_DEMO_DATA=$(cat /tmp/oro_install_APP_LOAD_DEMO_DATA)

GITHUB_TOKEN="790b15df052b6de8f2b710464293a095448689cf"
echo "$GITHUB_TOKEN" > /tmp/oro_install_GITHUB_TOKEN

echo "~~~~~~~~~~~~~~ Create symbol links   ~~~~~~~~~~~~~~"
cd /opt/project/oroapp/
ln -s /opt/project/orodata/vendor
cd /home/vagrant
ln -s /opt/project

echo "~~~~~~~~~~~~~~ Install Composer Meta ~~~~~~~~~~~~~~"
cd /home/vagrant
rm -rf .composer
wget -q http://172.17.3.91/project/rri/composerpacakge/composer_meta_v3.0.0.rc.zip && unzip composer_meta_v3.0.0.rc.zip
chown -R vagrant.vagrant .composer
rm -rf composer_meta_v3.0.0.rc.zip

echo "~~~~~~~~~~~~~~ Install Application Dependencies ~~~~~~~~~~~~~~"
# --- Install Dependencies ---
cd /opt/project/oroapp
cp ./config/parameters.yml.dist ./config/parameters.yml
ssh-keyscan -t rsa -H github.com >> /home/vagrant/.ssh/known_hosts
export COMPOSER_ALLOW_SUPERUSER=1
scl enable oro-php71 'GITHUB_TOKEN=$(cat /tmp/oro_install_GITHUB_TOKEN) && composer config -g github-oauth.github.com $GITHUB_TOKEN'
scl enable oro-php71 'composer install --prefer-dist --no-dev'

# --- Configure config/parameters.yml (to prevent composer interactive dialog) ---

sed -i "s/database_driver:[ ]*pdo_mysql/database_driver: pdo_pgsql/g" ./config/parameters.yml
sed -i "s/database_name:[ ]*[a-zA-Z0-9_]*/database_name: $DB_NAME/g" ./config/parameters.yml
sed -i "s/database_user:[ ]*root/database_user: $DB_USER/g" ./config/parameters.yml
sed -i "s/database_password:[ ]*null/database_password: '$DB_PASSWORD'/g" ./config/parameters.yml

sed -i "s/search_engine_name:[ ]*orm/search_engine_name: elastic_search/g" ./config/parameters.yml
sed -i "s/session_handler:[ ]*[\\.a-zA-Z0-9_]*/session_handler: 'snc_redis.session.handler'\\n    redis_dsn_session: 'redis:\\/\\/127.0.0.1:6379\\/0'\\n    redis_dsn_cache: 'redis:\\/\\/127.0.0.1:6379\\/1'\\n    redis_dsn_doctrine: 'redis:\\/\\/127.0.0.1:6379\\/2'\\n    redis_setup: 'standalone'/g" ./config/parameters.yml

#sed -i "s/enterprise_licence:[ ]*null/enterprise_licence: '$LICENCE_KEY'/g" ./config/parameters.yml
sed -i "s/message_queue_transport:[ ]*dbal/message_queue_transport: amqp/g" ./config/parameters.yml
sed -i "s/message_queue_transport_config:[ ]*null/message_queue_transport_config: { host: 'localhost', port: '5672', user: '$RABBITMQ_USER', password: '$RABBITMQ_PASSWORD', vhost: '\\/' }/g" ./config/parameters.yml

echo "~~~~~~~~~~~~~~ Install OroPlatform Enterprise Edition Application ~~~~~~~~~~~~~~"
scl enable oro-php71 'APP_HOST=$(cat /tmp/oro_install_APP_HOST) && APP_USER=$(cat /tmp/oro_install_APP_USER) && APP_PASSWORD=$(cat /tmp/oro_install_APP_PASSWORD) && APP_LOAD_DEMO_DATA=$(cat /tmp/oro_install_APP_LOAD_DEMO_DATA) && php ./bin/console oro:install --env=dev --timeout=9000 --no-debug --application-url="http://$APP_HOST/" --organization-name="RRI" --user-name="$APP_USER" --user-email="admin@example.com" --user-firstname="ORO" --user-lastname="AAXIS" --user-password="$APP_PASSWORD" --sample-data=$APP_LOAD_DEMO_DATA'
