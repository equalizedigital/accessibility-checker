#!/bin/bash

# Reusable plugin slug (folder name and main file basename)
SLUG="accessibility-checker"
DIST_DIR="./dist"
ZIP_NAME="${SLUG}.zip"
EXTRACT_DIR="${DIST_DIR}/${SLUG}"
MAIN_FILE="${EXTRACT_DIR}/${SLUG}.php"

# Flag default
KEEP_BUILD_FOLDER=false
SHOW_HELP=false

# Parse boolean flags by presence
for arg in "$@"; do
  case "$arg" in
    --keep-build-folder) KEEP_BUILD_FOLDER=true ;;
    --help|-h) SHOW_HELP=true ;;
  esac
done

if [ "$SHOW_HELP" = true ]; then
  echo "Usage: $0 [--keep-build-folder]"; exit 0; fi

echo "KEEP_BUILD_FOLDER=$KEEP_BUILD_FOLDER"

# Ensure dist directory exists
mkdir -p "$DIST_DIR"

# Build initial zip
npx wp-scripts plugin-zip --no-root-folder || { echo "ERROR: wp-scripts plugin-zip failed"; exit 1; }

# Clear previous extracted folder
rm -rfd "${EXTRACT_DIR:?}"

# Unzip build into extract dir
unzip "$ZIP_NAME" -d "$EXTRACT_DIR" > /dev/null || { echo "ERROR: unzip failed"; exit 1; }

# Some systems plugin-zip might work different on and create an extra folder level
if [ -d "${EXTRACT_DIR:?}/${SLUG:?}" ]; then
  shopt -s dotglob nullglob
  mv "${EXTRACT_DIR:?}/${SLUG:?}/"* "${EXTRACT_DIR:?}/"
  rm -r "${EXTRACT_DIR:?}/${SLUG:?}"
  shopt -u dotglob nullglob
fi

# Remove unwanted files if they exist
[ -f "$EXTRACT_DIR/package.json" ] && rm "$EXTRACT_DIR/package.json"
[ -f "$EXTRACT_DIR/README.md" ] && rm "$EXTRACT_DIR/README.md"
# This is a nearly 1mb test folder we don't need in production
[ -d "$EXTRACT_DIR/vendor/davechild/textstatistics/tests" ] && rm -r "$EXTRACT_DIR/vendor/davechild/textstatistics/tests"

# Remove original build zip
rm "$ZIP_NAME"

# Extract version from main plugin file header (first matching Version: line)
if [ ! -f "$MAIN_FILE" ]; then
  echo "ERROR: Main plugin file not found at $MAIN_FILE"; exit 1; fi
VERSION=$(sed -n 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*\([0-9][0-9A-Za-z._-]*\).*/\1/p' "$MAIN_FILE" | head -n1)
if [ -z "$VERSION" ]; then
  echo "ERROR: Could not extract version from $MAIN_FILE"; grep -n "Version" "$MAIN_FILE" || true; exit 1; fi

echo "Building plugin package for version: $VERSION"

# Create final distributable zip
cd "$DIST_DIR" || exit 1
FINAL_ZIP="${SLUG}-${VERSION}.zip"
zip -r "$FINAL_ZIP" "$SLUG" > /dev/null || { echo "ERROR: zip failed"; exit 1; }

# Remove .po files (ignore errors if none)
zip -d "$FINAL_ZIP" "${SLUG}/languages/*.po" > /dev/null || true

cd ..

# Optionally clean extracted folder
if [ "$KEEP_BUILD_FOLDER" = false ]; then
  rm -r "${EXTRACT_DIR:?}"
fi

echo "Done: $FINAL_ZIP"

