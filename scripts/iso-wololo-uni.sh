#!/bin/bash

# iso-wololo-uni
# orchestra the start of conversion

function usage() {
  echo -e "This program convert one project to a new one in UTF-8 encoding."
  echo -e "usage: convert [code|database] '/project/to/converter' '/path/new-converted-project'"
  exit 1
}

if [[ ! "$(command -v php)" ]]; then
  echo "nao tem o php mano, instala ai...!!!"
  exit 0
fi

if [ $# -lt 1 ]; then
  usage
fi

php core/convert ${@}
