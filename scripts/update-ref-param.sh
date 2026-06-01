#!/bin/bash
# update-ref-param.sh
# Update the EDAC_REF_PARAM constant in accessibility-checker.php with the provided value.
# Usage:
#   ./update-ref-param.sh "my-ref-value"
# If the value is omitted, it will set EDAC_REF_PARAM to an empty string.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_ROOT="$(dirname "$SCRIPT_DIR")"   # parent of scripts/
TARGET_FILE="$PLUGIN_ROOT/accessibility-checker.php"
NEW_VALUE="${1:-}"  # default to empty string if no arg

if [[ ! -f "$TARGET_FILE" ]]; then
  echo "Error: accessibility-checker.php not found at $TARGET_FILE" >&2
  exit 1
fi

# Escape backslashes and ampersands for sed replacement
ESCAPED_VALUE=${NEW_VALUE//\\/\\\\}
ESCAPED_VALUE=${ESCAPED_VALUE//&/\&}

# Verify the EDAC_REF_PARAM define exists
if ! grep -q "define( 'EDAC_REF_PARAM'" "$TARGET_FILE"; then
  echo "Error: Could not find EDAC_REF_PARAM define in $TARGET_FILE" >&2
  exit 1
fi

# Replace the value inside: define( 'EDAC_REF_PARAM', '...' );
# Keep surrounding formatting intact
sed -E -i "s/(define\( 'EDAC_REF_PARAM', ')[^']*(' \);)/\1${ESCAPED_VALUE}\2/" "$TARGET_FILE"

# Quick verification output
CURRENT=$(grep -E "define\( 'EDAC_REF_PARAM'" "$TARGET_FILE" | sed -E "s/^.*define\( 'EDAC_REF_PARAM', '([^']*)' \);.*$/\1/")

echo "Updated EDAC_REF_PARAM to: '$CURRENT'"
echo "File: $TARGET_FILE"
