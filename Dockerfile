ARG ARCH=amd64
FROM bayrell/alpine_php_fpm:8.0-${ARCH}

RUN apk update; \
	apk add luarocks5.1 lua-resty-jwt lua5.1-dev lua5.1-cjson lua5.1-md5 \
		lua5.1-curl gcc musl-dev openssl-dev nginx-mod-http-lua; \
	rm -rf /var/www/html; \
	rm -rf /var/cache/apk/*; \
	echo "Ok"

COPY files /
RUN cd ~; \
	chmod +x /root/run.sh; \
	chmod +x /root/htpasswd.sh; \
	echo "Ok"