FROM php:7.4-apache
MAINTAINER craigk5n <craig@k5n.us>

RUN docker-php-ext-install mysqli

#COPY docker/cron-hourly /tmp/cron.hourly
#RUN crontab /tmp/cron.hourly

# Run the cron every hour
#RUN echo '0 * * * * php /var/www/html/tools/send_reminders.php' > /etc/crontabs/root
#TODO: setup cron

#RUN chown -R www-data:www-data /var/www

