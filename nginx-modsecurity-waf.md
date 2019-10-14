# Nginx ModSecurity WAF

## Create dockerfile in folder `./src-owasp/.docker/nginx/Dockerfile`
```./src-owasp/.docker/nginx/Dockerfile
FROM owasp/modsecurity:latest

RUN set -ex; \
    wget https://github.com/SpiderLabs/owasp-modsecurity-crs/archive/v3.0.2.tar.gz; \
    tar -xzf v3.0.2.tar.gz -C /usr/local

COPY ./src-owasp/src /var/www/html
```

## Create ModSecurity config file in `./src-owasp/.docker/modsecurity.d/include.conf`
```./src-owasp/.docker/modsecurity.d/include.conf
include "/etc/modsecurity.d/modsecurity.conf"
SecRuleEngine On

# Basic test rule
SecRule ARGS:testparam "@contains test" "id:1234,deny,log,status:404"
SecRule ARGS:blogtest "@contains test" "id:1111,deny,status:403"
SecRule REQUEST_URI "@beginsWith /admin" "phase:2,t:lowercase,id:2222,deny,status:500,msg:'block admin'"
```
