##!/bin/sh

# Check if phpcs is available
if ! command -v phpcs &> /dev/null
then
    # If phpcs is not found, modify the PATH
    export PATH="$HOME/.config/composer/vendor/bin:$PATH"
fi
if test $(git rev-parse --abbrev-ref HEAD) = "development" ; then
  echo "Cannot commit on development"
  exit 1
fi

CHANGED_FILES=`git diff --name-only --diff-filter=ACMR HEAD | grep \\\\.php`
echo $CHANGED_FILES;
git add *

CHANGED_FILES=`git diff --name-only --diff-filter=ACMR HEAD | grep \\\\.php`
if [ "$CHANGED_FILES" != "" ]
then
   echo "Running Code Sniffer. Code standard VIP Minimum."
    phpcs -s $CHANGED_FILES
    if [ $? != 0 ]
    then
        echo "Fix the error before commit!"
        exit 1
    fi
fi
