#!/bin/sh

/etc/init.d/sysklogd stop
VARLOGS="auth.log boot btmp daemon.log debug dmesg dpkg.log dpkg.log.1 kern.log lpr.log mail.err mail.info mail.log mail.warn messages syslog udev user.log vsftpd.log wtmp"

cd /var/log

echo "">my_log.log

for ii in $VARLOGS; do
  echo -n > $ii
  rm -f $ii.? $ii.?.gz
done

/etc/init.d/samba stop
rm -f /var/log/samba/*

rm -f /var/lib/dhcp3/*

for ii in /var/log/proftpd/* /var/log/postgresql/* /var/log/apache2/*; do
  echo -n > $ii
done
