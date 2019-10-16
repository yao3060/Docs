# Nginx ModSecurity WAF

## Create dockerfile in folder 
./src-owasp/.docker/nginx/Dockerfile
```dockerfile
FROM owasp/modsecurity:latest

LABEL maintainer="Chaim Sanders <chaim.sanders@gmail.com>"

ARG COMMIT=v3.3/dev
ARG BRANCH=v3.3/dev
ARG REPO=SpiderLabs/owasp-modsecurity-crs
ENV WEBSERVER=Nginx
ENV PARANOIA=1
ENV ANOMALYIN=5
ENV ANOMALYOUT=4

RUN apt-get update && \
  apt-get -y install python git ca-certificates iproute2 && \
  mkdir /opt/owasp-modsecurity-crs-3.2 && \
  cd /opt/owasp-modsecurity-crs-3.2 && \
  git init && \
  git remote add origin https://github.com/${REPO} && \
  git fetch --depth 1 origin ${BRANCH} && \
  git checkout ${COMMIT} && \
  mv crs-setup.conf.example crs-setup.conf && \
  ln -sv /opt/owasp-modsecurity-crs-3.2 /etc/modsecurity.d/owasp-crs && \
  printf "include /etc/modsecurity.d/owasp-crs/crs-setup.conf\ninclude /etc/modsecurity.d/owasp-crs/rules/*.conf" >> /etc/modsecurity.d/include.conf && \
  sed -i -e 's/SecRuleEngine DetectionOnly/SecRuleEngine On/g' /etc/modsecurity.d/modsecurity.conf


COPY ./src-owasp/src /var/www/html
EXPOSE 80

COPY ./src-owasp/.docker/nginx/docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
```

**docker-entrypoint.sh**
```shell
#!/bin/bash

# Paranoia Level
$(python <<EOF
import re
import os
out=re.sub('(#SecAction[\S\s]{7}id:900000[\s\S]*tx\.paranoia_level=1\")','SecAction \\\\\n  \"id:900000, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:tx.paranoia_level='+os.environ['PARANOIA']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Executing Paranoia Level
$(python <<EOF
import re
import os
if "EXECUTING_PARANOIA" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{7}id:900001[\s\S]*tx\.executing_paranoia_level=1\")','SecAction \\\\\n  \"id:900001, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:tx.executing_paranoia_level='+os.environ['EXECUTING_PARANOIA']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Enforce Body Processor URLENCODED
$(python <<EOF
import re
import os
if "ENFORCE_BODYPROC_URLENCODED" in os.environ:
   out=re.sub('(#SecAction[\S\s]{7}id:900010[\s\S]*tx\.enforce_bodyproc_urlencoded=1\")','SecAction \\\\\n  \"id:900010, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:tx.enforce_bodyproc_urlencoded='+os.environ['ENFORCE_BODYPROC_URLENCODED']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Inbound and Outbound Anomaly Score
$(python <<EOF
import re
import os
out=re.sub('(#SecAction[\S\s]{6}id:900110[\s\S]*tx\.outbound_anomaly_score_threshold=4\")','SecAction \\\\\n  \"id:900110, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:tx.inbound_anomaly_score_threshold='+os.environ['ANOMALYIN']+','+'  \\\\\n   setvar:tx.outbound_anomaly_score_threshold='+os.environ['ANOMALYOUT']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# HTTP methods that a client is allowed to use.
$(python <<EOF
import re
import os
if "ALLOWED_METHODS" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900200[\s\S]*\'tx\.allowed_methods=[A-Z\s]*\'\")','SecAction \\\\\n  \"id:900200, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.allowed_methods='+os.environ['ALLOWED_METHODS']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Content-Types that a client is allowed to send in a request.
$(python <<EOF
import re
import os
if "ALLOWED_REQUEST_CONTENT_TYPE" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900220[\s\S]*\'tx.allowed_request_content_type=[a-z|\-\+\/]*\'\")','SecAction \\\\\n  \"id:900220, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.allowed_request_content_type='+os.environ['ALLOWED_REQUEST_CONTENT_TYPE']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Content-Types charsets that a client is allowed to send in a request.
$(python <<EOF
import re
import os
if "ALLOWED_REQUEST_CONTENT_TYPE_CHARSET" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900270[\s\S]*\'tx.allowed_request_content_type_charset=[|\-a-z0-9]*\'\")','SecAction \\\\\n  \"id:900270, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.allowed_request_content_type_charset='+os.environ['ALLOWED_REQUEST_CONTENT_TYPE_CHARSET']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Allowed HTTP versions.
$(python <<EOF
import re
import os
if "ALLOWED_HTTP_VERSIONS" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900230[\s\S]*\'tx.allowed_http_versions=[HTP012\/\.\s]*\'\")','SecAction \\\\\n  \"id:900230, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.allowed_http_versions='+os.environ['ALLOWED_HTTP_VERSIONS']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Forbidden file extensions.
$(python <<EOF
import re
import os
if "RESTRICTED_EXTENSIONS" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900240[\s\S]*\'tx.restricted_extensions=[\.a-z\s\/]*\/\'\")','SecAction \\\\\n  \"id:900240, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.restricted_extensions='+os.environ['RESTRICTED_EXTENSIONS']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Forbidden request headers.
$(python <<EOF
import re
import os
if "RESTRICTED_HEADERS" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900250[\s\S]*\'tx.restricted_headers=[a-z\s\/\-]*\'\")','SecAction \\\\\n  \"id:900250, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.restricted_headers='+os.environ['RESTRICTED_HEADERS']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# File extensions considered static files.
$(python <<EOF
import re
import os
if "STATIC_EXTENSIONS" in os.environ:
   out=re.sub('(#SecAction[\S\s]{6}id:900260[\s\S]*\'tx.static_extensions=\/[a-z\s\/\.]*\'\")','SecAction \\\\\n  \"id:900260, \\\\\n   phase:1, \\\\\n   nolog, \\\\\n   pass, \\\\\n   t:none, \\\\\n   setvar:\'tx.static_extensions='+os.environ['STATIC_EXTENSIONS']+'\'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if number of arguments is too high
$(python <<EOF
import re
import os
if "MAX_NUM_ARGS" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900300[\s\S]*tx\.max_num_args=255\")','SecAction \\\\\n \"id:900300, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.max_num_args='+os.environ['MAX_NUM_ARGS']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if the length of any argument name is too high
$(python <<EOF
import re
import os
if "ARG_NAME_LENGTH" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900310[\s\S]*tx\.arg_name_length=100\")','SecAction \\\\\n \"id:900310, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.arg_name_length='+os.environ['ARG_NAME_LENGTH']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if the length of any argument value is too high
$(python <<EOF
import re
import os
if "ARG_LENGTH" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900320[\s\S]*tx\.arg_length=400\")','SecAction \\\\\n \"id:900320, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.arg_length='+os.environ['ARG_LENGTH']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if the total length of all combined arguments is too high
$(python <<EOF
import re
import os
if "TOTAL_ARG_LENGTH" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900330[\s\S]*tx\.total_arg_length=64000\")','SecAction \\\\\n \"id:900330, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.total_arg_length='+os.environ['TOTAL_ARG_LENGTH']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if the total length of all combined arguments is too high
$(python <<EOF
import re
import os
if "MAX_FILE_SIZE" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900340[\s\S]*tx\.max_file_size=1048576\")','SecAction \\\\\n \"id:900340, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.max_file_size='+os.environ['MAX_FILE_SIZE']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

# Block request if the total size of all combined uploaded files is too high
$(python <<EOF
import re
import os
if "COMBINED_FILE_SIZES" in os.environ: 
   out=re.sub('(#SecAction[\S\s]{6}id:900350[\s\S]*tx\.combined_file_sizes=1048576\")','SecAction \\\\\n \"id:900350, \\\\\n phase:1, \\\\\n nolog, \\\\\n pass, \\\\\n t:none, \\\\\n setvar:tx.combined_file_sizes='+os.environ['COMBINED_FILE_SIZES']+'\"',open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','r').read())
   open('/etc/modsecurity.d/owasp-crs/crs-setup.conf','w').write(out)
EOF
) && \

if [ $WEBSERVER = "Apache" ]; then
  if [ ! -z $PROXY ]; then
    if [ $PROXY -eq 1 ]; then
      WEBSERVER_ARGUMENTS='-D crs_proxy'
      if [ -z "$UPSTREAM" ]; then
        export UPSTREAM=$(/sbin/ip route | grep ^default | perl -pe 's/^.*?via ([\d.]+).*/$1/g'):81
      fi
    fi
  fi
elif [ $WEBSERVER = "Nginx" ]; then
  WEBSERVER_ARGUMENTS=''
fi


exec "$@" $WEBSERVER_ARGUMENTS
```

## owasp nginx default config file
/etc/nginx/conf.d/default.conf
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
    environment:
      - SERVERNAME=localhost
      #############################################
      # CRS Variables
      #############################################
      # Paranoia Level
      - PARANOIA=1
      # Inbound and Outbound Anomaly Score Threshold
      - ANOMALYIN=5
      - ANOMALYOUT=4
      # Executing Paranoia Level
      # - EXECUTING_PARANOIA=2
    ports:
      - 8080:80
    volumes:
      - ./src-owasp/.docker/nginx/conf.d:/etc/nginx/conf.d
      - ./src-owasp/.docker/modsecurity.d/include.conf:/etc/modsecurity.d/include.conf
      - ./src-owasp/.docker/nginx/modsec:/etc/nginx/modsec
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
