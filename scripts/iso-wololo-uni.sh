#!/bin/bash

# iso-wololo-uni
# orchestra the start of conversion

function usage() {
  echo -e "\n  This program convert one project to a new one in UTF-8 encoding."
  echo -e "  usage: convert [code|database] '/project/to/converter' '/path/new-converted-project'\n"
  exit 1
}

if [[ ! "$(command -v php)" ]]; then
  echo "PHP not found."
  exit 0
fi

if [ "$1" == "code" ]; then
  if [ $# -lt 5 ]; then
    usage
  fi
fi

php core/convert ${@}
