#!/bin/bash
set -e

DIR=`dirname "$(readlink -f $0)"`
pushd "$DIR/docker"
trap popd EXIT

# setup config.php
if [[ ! -f config.php ]]
then
    echo "creating configuration"
    cp ../website/config.php.template config.php
    sed 's/"localhost"/"db"/g' -i config.php
    echo "please edit your website configuration"
    datebefore=$(stat -c %y config.php)
    "${EDITOR:-vi}" config.php
    dateafter=$(stat -c %y config.php)
    if [[ $datebefore = $dateafter ]]
    then
        # nothing changed
        echo "editing canceled"
        rm config.php 
        exit 0
    fi
fi

# setup style.less
if [[ ! -f style.less ]]
then
    echo "creating style"
    cp style.less.template style.less
    echo "please edit your website configuration"
    datebefore=$(stat -c %y style.less)
    "${EDITOR:-vi}" style.less
    dateafter=$(stat -c %y style.less)
    if [[ $datebefore = $dateafter ]]
    then
        # nothing changed
        echo "editing canceled"
        rm style.less
        exit 0
    fi
fi

# setup Apache ssmtp (email)
if [[ ! -f ssmtp.conf ]]
then
    cp ssmtp.conf.template ssmtp.conf
    echo "please enter your mailserver configuration"
    datebefore=$(stat -c %y ssmtp.conf)
    "${EDITOR:-vi}" ssmtp.conf
    dateafter=$(stat -c %y ssmtp.conf)
    if [[ $datebefore = $dateafter ]]
    then
        # nothing changed
        echo "editing canceled"
        rm ssmtp.conf
        exit 0
    fi
fi

# start docker-compose, run in background
echo "starting docker-compose"
docker-compose up -d --build

echo "-------------------------------------"
echo "system up"
echo "to stop, run "
echo "  cd docker && docker-compose down"
echo "-------------------------------------"
