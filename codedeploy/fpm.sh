#!/bin/bash -e

if service --status-all | grep -Fq 'php7.3-fpm'; then    
  sudo service php7.3-fpm reload
else
  sudo service php7.0-fpm reload
fi
