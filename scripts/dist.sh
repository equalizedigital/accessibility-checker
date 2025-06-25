#!/bin/bash

# Define the flag variable and set default value
KEEP_BUILD_FOLDER=false

# Parse command-line options
while getopts ":-:" opt; do
  case $opt in
    -)
      case "${OPTARG}" in
        keep-build-folder)
          val="${!OPTIND}"; OPTIND=$(( $OPTIND + 1 ))
          KEEP_BUILD_FOLDER=$val
          ;;
        *)
          ;;
      esac;;
    *)
      ;;
  esac
done

# Ensure ./dist directory exists, if not create it
mkdir -p ./dist

# Run the wp-scripts command that produces the zip
npx wp-scripts plugin-zip

# Always clear the dist/accessibility-checker folder before unzipping
rm -rfd ./dist/accessibility-checker

# Unzip the zip into its own folder so we can repackage with some changes
unzip accessibility-checker.zip -d ./dist/accessibility-checker

# The plugin-zip commands includes package.json, which is not needed for the plugin, so remove it and the repo README
rm ./dist/accessibility-checker/package.json
rm ./dist/accessibility-checker/README.md

# Remove the unneeded (almost 1MB) tests folder for textstatistics package
rm ./dist/accessibility-checker/vendor/davechild/textstatistics/tests -r

# Remove the original zip
rm accessibility-checker.zip

# Get the string at the end of the line starting with ' * Version:' from the main plugin file
VERSION=$(grep " * Version:" ./dist/accessibility-checker/accessibility-checker.php | grep -o '[0-9.]*\(-[a-zA-Z0-9.]*\)*')

# Move into the dist folder and zip the plugin's folder
cd ./dist
zip -r accessibility-checker-$VERSION.zip accessibility-checker

# Remove the po files from the zip file
zip -d accessibility-checker-$VERSION.zip ./accessibility-checker/languages/*.po

# Drop back into the original dir
cd ..

# Skip this step if the 'keep-build-folder' flag is true
if [ "$KEEP_BUILD_FOLDER" = false ] ; then
  rm -r ./dist/accessibility-checker
fi
