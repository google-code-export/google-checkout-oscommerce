#!/bin/bash
#
# This script wraps the python install script for Unix and is analogous to 
# install.bat for Windows.
#
# Author: Ed Davisson (ed.davisson@gmail.com)

GC_CATALOG="catalog/"
GOLDEN_CATALOG="tools/golden/oscommerce-2.2rc2a/catalog/"
INSTALLER="python tools/installer.py"

function main {
  if [[ "$#" -eq 1 ]]; then
    target_catalog="${1}/"
    ${INSTALLER} --zip_backup ${GC_CATALOG} ${GOLDEN_CATALOG} ${target_catalog}
  else
    ${INSTALLER} --ui --zip_backup
  fi
}

# Execute main function, passing along all command line args.
main "$@"