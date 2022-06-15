ARG ARCH=amd64
FROM bayrell/alpine_php_fpm:8.0-${ARCH}

RUN apk update && apk add lua-resty-jwt lua5.1-cjson nginx-mod-http-lua && rm -rf /var/cache/apk/*

COPY files /
RUN cd ~; \
	rm /var/www/html/index.html; \
	chmod +x /root/run.sh; \
	chmod +x /root/htpasswd.sh; \
	echo "Ok"