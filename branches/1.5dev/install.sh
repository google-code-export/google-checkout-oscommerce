#!/bin/bash
#
# This script wraps the python install script for Unix and is analogous to 
# install.bat for Windows.
#
# Author: Ed Davisson (ed.davisson@gmail.com)

DIFF3="/usr/bin/diff3"
GOLDEN_CATALOG="tools/golden/oscommerce-2.2rc2a/catalog/"
INSTALLER="python tools/installer.py"

function main {
  if [[ "$#" -eq 1 ]]; then
    target_dir="${1}/"
    ${INSTALLER} --diff3=${DIFF3} ${GOLDEN_CATALOG} ${target_dir}
  else
    ${INSTALLER} --diff3=${DIFF3} --ui
  fi
}

# Execute main function, passing along all command line args.
main "$@"