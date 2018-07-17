#!/usr/bin/env bash


echo "*********************************************************************************"
echo "************** Post-installation Environment Configuration **************"
echo "*********************************************************************************"

echo "\n~~~~~~~~~~~~~~ Schedule Periodical Command Execution ~~~~~~~~~~~~~~\n"
echo "*/1 * * * * scl enable oro-php71 'php /opt/project/oroapp/bin/console oro:cron --env=dev > /dev/null'" > /var/spool/cron/nginx
echo "~~~~~~~~~~~~~~ Configure and Run Required Background Processes ~~~~~~~~~~~~~~"

cat >> /etc/supervisord.conf <<____SUPERVISORDTEMPLATE
[program:oro_web_socket]
command=scl enable oro-php71 'php ./bin/console clank:server --env=dev'
numprocs=1
autostart=true
autorestart=true
directory=/opt/project/oroapp
user=vagrant
redirect_stderr=true
[program:oro_message_consumer]
command=scl enable oro-php71 'php ./bin/console oro:message-queue:consume --env=dev'
process_name=%(program_name)s_%(process_num)02d
numprocs=5
autostart=true
autorestart=true
directory=/opt/project/oroapp
user=vagrant
redirect_stderr=true
____SUPERVISORDTEMPLATE

systemctl restart supervisord
systemctl restart nginx

echo "**********************************************************************************************************************"
echo "************** Congratulations! You’ve Successfully Installed OroCommerce Application **********************************"
echo "**********************************************************************************************************************"
