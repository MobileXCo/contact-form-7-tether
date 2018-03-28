#!/bin/bash -e

if service --status-all | grep -Fq 'php7.1-fpm'; then    
  sudo service php7.1-fpm reload
else
  sudo service php7.0-fpm reload
fi
