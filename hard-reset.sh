#!/bin/bash

echo "You have chosen to remove and reinstall the wordpress dev and testing environments."
echo -n "This will delete the database and content files. Are you sure want to continue? (yes/NO): "

# Read user input into the variable "response"
read response

# Check if the response is "yes" and take action accordingly
if [ "$response" = "yes" ]; then
  echo "Ok. Removing and reinstalling the wordpress environments."
  echo "y" | npx wp-env destroy
  rm .wp-env.json
  rm -r ./.wp-env
  npm install
else
  echo "Ok, no changes were made."
fi

