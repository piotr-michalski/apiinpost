FROM nginx:1.27.4

RUN groupadd -g 1000 app && useradd -u 1000 -g app -m app

RUN mkdir -p /var/www/html/app
RUN chmod -R 755 /var/run
RUN chmod -R 755 /var/cache/nginx
RUN chmod -R 755 /var/log/nginx
RUN chown -R app:app /etc/nginx /var/www/html/app /var/log/nginx /var/cache/nginx /var/run
USER app
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
COPY . /var/www/html/app
WORKDIR /var/www/html/app

EXPOSE 8080
CMD ["nginx", "-g", "daemon off;"]
