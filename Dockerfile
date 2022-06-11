ARG ARCH=amd64
FROM bayrell/alpine_php_fpm:8.0-openresty-${ARCH}

COPY files /
RUN cd ~; \
	rm /var/www/html/index.html; \
	chmod +x /root/run.sh; \
	echo "Ok"