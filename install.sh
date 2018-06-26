#!/bin/bash

function check_list {
  if [[ ! "$(command -v composer)" ]]; then
    echo "Composer not installed."
    echo "use this command below to do it"
    echo -e "\tcurl -sS https://getcomposer.org/installer | php"
    if [[ "$(command -v php --version)" ]]; then
      echo "Install PHP >= 7.1 before go ahead and after that install composer"
      exit 1
    fi
    if [[ ! "$(command -v mysql)" ]]; then
      echo "Install MySQL before go ahead..."
      exit 1
    fi
    if [[ "$(command -v bzip2)" ]] || [[ "$(command -v bunzip)" ]]; then
      echo "install bzip2/bunzip to go forward!"
      exit 1
    fi
    exit 1
  else
    return 0
  fi
}

function install {
  composer install
  chmod +x ./scripts/*.sh
  chmod +x ./bin/wololo
  ln -s $(pwd)/bin/wololo /usr/bin/wololo
}

if check_list; then
  echo "Conversor ver. 0.0.1-ALFA"
  install && \
  echo "Installed"
fi
