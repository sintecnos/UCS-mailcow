# Docker image
FROM python:3-alpine

WORKDIR /app

RUN apk --no-cache add build-base openldap-dev python3-dev
RUN pip3 install python-ldap sqlalchemy requests

COPY templates ./templates
COPY api.py filedb.py syncer.py ./

VOLUME [ "/db" ]
VOLUME [ "/conf/dovecot" ]
VOLUME [ "/conf/sogo" ]

ENTRYPOINT [ "python3", "syncer.py" ]