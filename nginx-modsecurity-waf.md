# Nginx ModSecurity WAF

## Create dockerfile in folder 
./src-owasp/.docker/nginx/Dockerfile
```dockerfile
FROM owasp/modsecurity:latest

RUN set -ex; \
    wget https://github.com/SpiderLabs/owasp-modsecurity-crs/archive/v3.0.2.tar.gz; \
    tar -xzf v3.0.2.tar.gz -C /usr/local

COPY ./src-owasp/src /var/www/html
```

## Create ModSecurity config file in 
./src-owasp/.docker/modsecurity.d/include.conf
```nginx
include "/etc/modsecurity.d/modsecurity.conf"
# enable ModSecurity
SecRuleEngine On

# Basic test rule
## 返回 404： 如果有参数 testparam，并且参数的值里包括 test
SecRule ARGS:testparam "@contains test" "id:1234,deny,log,status:404"
SecRule ARGS:blogtest "@contains test" "id:1111,deny,status:403"

## 返回 500： 拦截 /admin 开头的请求
SecRule REQUEST_URI "@beginsWith /admin" "phase:2,t:lowercase,id:2222,deny,status:500,msg:'block admin'"
```

## owasp nginx default config file
```nginx
server {

    listen 80;
    server_name localhost;
    root /var/www/html;

    location / {
        proxy_pass http://openresty;
    }

    location /echo {
        default_type text/plain;
        return 200 "Thank you for requesting ${request_uri}\n";
    }


    error_page 404              /404.html;
    location = /404.html {
        modsecurity off;
        internal;
    }

    error_page 403              /403.html;
    location = /403.html {
        modsecurity off;
        internal;
    }

    error_page 500 502 503 504  /50x.html;
    location = /50x.html {
        modsecurity off;
        internal;
    }
}

```

## Create docker-compose file
`./docker-compose.yml`
```docker
version: "3"
services: 
  owasp:
    build:
      context: .
      dockerfile: ./src-owasp/.docker/nginx/Dockerfile
    restart: always
    ports:
      - 8080:80
    volumes:
      - ./src-owasp/.docker/nginx/conf.d:/etc/nginx/conf.d
      - ./src-owasp/.docker/modsecurity.d/include.conf:/etc/modsecurity.d/include.conf
      - ./src-owasp/src:/var/www/html
      
  openresty:
    image: openresty/openresty:alpine
    restart: always
    ports:
      - 8081:80
 ```
 
 ## Create `Makefile`
 ```makefile
 CONTAINER_NAME 		:= owasp

start:
	docker-compose up --build

stop:
	docker-compose down

reload:
	docker exec  $(CONTAINER_NAME) nginx -s reload
```


## Start
```shell
make start
```
```
curl http://docker.local:8080/echo?blogtest=testtest
```
```html
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>403</title>
</head>
<body>
    This is 403 page
</body>
</html>
```

```
curl http://docker.local:8080/admin--adad 
```
```html
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>50x</title>
</head>

<body>
    this is 50x page
</body>

</html>
```
```log
owasp_1      | 2019/10/14 09:35:52 [error] 6#6: *2 [client 172.28.0.1] ModSecurity: Access denied with code 500 (phase 2). Matched "Operator `BeginsWith' with parameter `/admin' against variable `REQUEST_URI' (Value: `/admin--adad' ) [file "/etc/modsecurity.d/include.conf"] [line "7"] [id "2222"] [rev ""] [msg "block admin"] [data ""] [severity "0"] [ver ""] [maturity "0"] [accuracy "0"] [hostname "172.28.0.1"] [uri "/admin--adad"] [unique_id "157104575215.899152"] [ref "o0,6v4,12t:lowercase"], client: 172.28.0.1, server: localhost, request: "GET /admin--adad HTTP/1.1", host: "docker.local:8080"
owasp_1      | 172.28.0.1 - - [14/Oct/2019:09:35:52 +0000] "GET /admin--adad HTTP/1.1" 500 261 "-" "curl/7.54.0" "-"
```
