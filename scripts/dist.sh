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

# Ensure ./dist directory exists.
#rm -frd ./dist
mkdir -p ./dist

# Run the wp script that produces the zip
npx wp-scripts plugin-zip

# Always clear the dist/accessiblity-checker folder before unzipping
rm -rfd ./dist/accessibility-checker

# unzip the zip into its own folder so we can zip that
unzip accessibility-checker.zip -d ./dist/accessibility-checker

# plugin-zip includes package.json which is not needed for the plugin, so remove.
rm ./dist/accessibility-checker/package.json
rm ./dist/accessibility-checker/README.md

# remove the unneeded (almost 1MB) tests folder for textstatistics package
rm ./dist/accessibility-checker/vendor/davechild/textstatistics/tests -r

#remove the original zip
rm accessibility-checker.zip

# get the digits at the end of the line starting with ' * Version:' from the main plugin file
VERSION=$(grep " * Version:" ./dist/accessibility-checker/accessibility-checker.php | grep -o '[0-9.]*\(-[a-zA-Z0-9.]*\)*')

# Move into the dist folder and zip the plugin's folder
cd ./dist
zip -r accessibility-checker-$VERSION.zip accessibility-checker

#cleanup and drop back into the original dir
cd ..

# Skip this step if the 'keep-build-folder' flag is true
if [ "$KEEP_BUILD_FOLDER" = false ] ; then
  rm -r ./dist/accessibility-checker
fi
