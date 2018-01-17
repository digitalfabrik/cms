FROM php:5.6-apache

# install the PHP extensions we need
RUN set -ex; \
	\
	apt-get update; \
	apt-get install -y \
		libjpeg-dev \
		libpng-dev \
		sudo \
	; \
	rm -rf /var/lib/apt/lists/*; \
	\
	docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr; \
	docker-php-ext-install gd mysqli opcache
# TODO consider removing the *-dev deps and only keeping the necessary lib* packages

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
		echo 'opcache.memory_consumption=128'; \
		echo 'opcache.interned_strings_buffer=8'; \
		echo 'opcache.max_accelerated_files=4000'; \
		echo 'opcache.revalidate_freq=2'; \
		echo 'opcache.fast_shutdown=1'; \
		echo 'opcache.enable_cli=1'; \
	} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN a2enmod rewrite expires

VOLUME /var/www/html/wordpress

RUN mkdir /usr/src/wordpress
COPY . /usr/src/wordpress
# todo: RUN composer install
# RUN chmod +x /usr/src/wordpress/wp-activate-plugins.sh
RUN chown -R www-data:www-data /usr/src/wordpress
# RUN cd /usr/src/wordpress/; sudo -u www-data -i -- ./wp-activate-plugins.sh;

COPY docker-entrypoint.sh /usr/local/bin/

WORKDIR /var/www/html/wordpress

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

# run composer and activate plugins
# docker run --name cms-mariadb -v <FOLDER_WITH_DB_DUMP>:/docker-entrypoint-initdb.d -e MYSQL_ALLOW_EMPTY_PASSWORD=1 -d mariadb
# docker run --name cms --link cms-mariadb -p 80:80 -e WORDPRESS_DB_HOST=cms-mariadb -e WORDPRESS_DB_USER=wordpress -e WORDPRESS_DB_PASSWORD=dummy -d <THIS_DOCKER>
