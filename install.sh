#!/bin/bash
#
# This script wraps the python install script.

INSTALLER="python tools/installer.py"
CATALOG="catalog/"
GOLDEN_CATALOG="tools/golden/oscommerce-2.2rc2a/catalog/"

function main {
  # Check that we have the right number of command line arguments.
  if [[ "$#" -eq 1 ]]; then
    TARGET=${1}
    ${INSTALLER} ${CATALOG} ${GOLDEN_CATALOG} ${TARGET}
  else
    echo "You need to supply the directory to install to."
  fi
}

# Execute main function, passing along all command line args.
main "$@"