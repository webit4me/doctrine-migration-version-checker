# doctrine-migration-version-checker

[![Build Status](https://travis-ci.org/ministryofjustice/doctrine-migration-version-checker.svg?branch=master)](https://travis-ci.org/ministryofjustice/doctrine-migration-version-checker)

[![codecov](https://codecov.io/gh/ministryofjustice/doctrine-migration-version-checker/branch/master/graph/badge.svg)](https://codecov.io/gh/ministryofjustice/doctrine-migration-version-checker)

A small library to interact with doctrine's configuration object to retrieve information about migration version

## Composer container

To install dependencies using docker

`docker-compose run composer`

To run other composer commands using docker such as update

`docker-compose run composer composer update`

### Unit test container

To run unit tests in a docker container

docker-compose run unit-test

To rebuild the container after local changes

docker-compose build unit-test
