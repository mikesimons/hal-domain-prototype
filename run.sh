#!/bin/bash

[ -z "$(which docker)" -a -z "$(which docker-io)" ]
DOCKER=$?
ES_PROVIDER=
ES_TARBALL_PID=

if [ $DOCKER ]; then
	ES_PROVIDER="es_docker"
else
	ES_PROVIDER="es_tarball"
fi

function es_docker_start {
	docker pull tutum/elasticsearch
	docker inspect protoes &>> /dev/null
	if [ $! ]; then
		echo "Starting existing container"
		docker start protoes
		docker ps
	else
		echo "Creating new container"
		docker run -P -d --name protoes tutum/elasticsearch &>> /dev/null
	fi
}

function es_docker_stop {
	docker kill protoes
}

function es_tarball_start {
	if [ ! -e "elasticsearch-1.2.2.tar.gz" ]; then
		wget https://download.elasticsearch.org/elasticsearch/elasticsearch/elasticsearch-1.2.2.tar.gz
	fi
	tar -xf elasticsearch-1.2.2.tar.gz
	(cd elasticsearch-1.2.2/bin && ./elasticsearch &)
	ES_TARBALL_PID=$!
}

function es_tarball_stop {
	kill $ES_TARBALL_PID
}

function clean_up {
	$ES_PROVIDER"_stop"
	exit
}

trap clean_up SIGHUP SIGINT SIGTERM

composer install

echo "Starting elasticsearch..."
$ES_PROVIDER"_start"

echo
echo "Sleeping to give elasticsearch a chance..."
echo
sleep 10

./populate-es.sh
php -S 0.0.0.0:8000 idea1-web.php
