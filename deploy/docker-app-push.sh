#!/usr/bin/env bash

DOCKER_TAG=$(echo ${TRAVIS_TAG} | sed -E 's~^v(.*)~\1~')

docker login -u "${DOCKER_USERNAME}" -p "${DOCKER_PASSWORD}"
docker build . -t knitpk/api:${DOCKER_TAG}
docker push knitpk/api:${DOCKER_TAG}