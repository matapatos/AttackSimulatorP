#!/bin/sh

VARLOGS="auth.log boot btmp daemon.log debug dmesg dpkg.log dpkg.log.1 kern.log lpr.log mail.err mail.info mail.log mail.warn messages syslog udev user.log vsftpd.log wtmp"

cd /var/log

for ii in $VARLOGS; do
	STRING = $(cat $ii)
	if [ - n "$STRING" ]; then
        echo $ii"-->" "$STRING" >> /var/log/my_log.log
done

for ii in /var/log/proftpd/controls.log /var/log/proftpd/proftpd.log /var/log/postgresql/postgresql-8.3-main.log /var/log/postgresql/postgresql-8.3-main.log.1 /var/log/apache2/access.log /var/log/apache2/access.log.1 /var/log/apache2/error.log /var/log/apache2/error.log.1; do
	STRING = $(cat $ii)
	if [ - n "$STRING" ]; then
        echo $ii"-->" "$STRING" >> /var/log/my_log.log
done