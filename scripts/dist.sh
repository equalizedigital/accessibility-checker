#Remove the contents of the dist folder
rm -frd ./dist
mkdir ./dist

#Run the wp script that produces the zip
npx wp-scripts plugin-zip

# unzip the zip into its own folder so we can zip that
unzip accessibility-checker-audit.zip -d ./dist/accessibility-checker-audit 

# plugin-zip includes package.json which is not needed for the plugin, so remove.
rm ./dist/accessibility-checker-pro/package.json

#remove the original zip
rm accessibility-checker.zip

#move into the dist folder and zip the plugin's folder
cd ./dist
zip -r accessibility-checker-audit.zip ./accessibility-checker-audit

#cleanup and drop back into the original dir
rm -r ./accessibility-checker-audit
cd ..