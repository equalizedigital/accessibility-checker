#!/usr/bin/env bash

################################################################################
# Changelog Validation Script
################################################################################
#
# This script validates the changelog.txt file format according to project rules.
#
# Usage:
#   ./scripts/validate-changelog.sh [path/to/changelog.txt]
#
# If no path is provided, defaults to 'changelog.txt' in the current directory.
#
# Validation Rules:
#   1. File must start with plugin name in triple asterisk header: *** Plugin Name ***
#   2. Exactly one blank line must separate each section
#   3. Version lines must follow format: YYYY-MM-DD - version X.X.X
#   4. Dates must be valid calendar dates
#   5. Versions must be in descending chronological order (newest first)
#   6. Each list item must follow format: * Keyword - Description
#   7. Keywords must be capitalized (first letter uppercase)
#   8. Each list item must be a single line
#   9. Special case: "* Initial release." is allowed for first version
#
# Allowed Keywords (case-insensitive, but must be capitalized):
#   Add, Added, Feature, New, Developer, Dev, Tweak, Changed, Update,
#   Delete, Remove, Fixed, Fix
#
# Exit Codes:
#   0 - Validation passed
#   1 - Validation failed
#
################################################################################


# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Allowed keywords (lowercase for comparison)
KEYWORDS=("add" "added" "feature" "new" "developer" "dev" "tweak" "changed" "update" "delete" "remove" "fixed" "fix")

# Capitalized versions for display
KEYWORD_CAPS=("Add" "Added" "Feature" "New" "Developer" "Dev" "Tweak" "Changed" "Update" "Delete" "Remove" "Fixed" "Fix")

# Error tracking
ERROR_COUNT=0
CHANGELOG_FILE="${1:-changelog.txt}"

################################################################################
# Helper Functions
################################################################################

# Print error message
error() {
    echo -e "${RED}ERROR:${NC} $1"
    ((ERROR_COUNT++))
}

# Print warning message
warning() {
    echo -e "${YELLOW}WARNING:${NC} $1"
}

# Print success message
success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Capitalize first letter of a word
capitalize() {
    local word="$1"
    echo "$(tr '[:lower:]' '[:upper:]' <<< ${word:0:1})${word:1}"
}

# Check if a keyword is valid (case-insensitive)
is_valid_keyword() {
    local keyword="$1"
    local keyword_lower=$(echo "$keyword" | tr '[:upper:]' '[:lower:]')

    for valid in "${KEYWORDS[@]}"; do
        if [[ "$keyword_lower" == "$valid" ]]; then
            return 0
        fi
    done
    return 1
}

# Get the properly capitalized version of a keyword
get_proper_capitalization() {
    local keyword="$1"
    local keyword_lower=$(echo "$keyword" | tr '[:upper:]' '[:lower:]')

    for i in "${!KEYWORDS[@]}"; do
        if [[ "$keyword_lower" == "${KEYWORDS[$i]}" ]]; then
            echo "${KEYWORD_CAPS[$i]}"
            return 0
        fi
    done
    echo "$keyword"
}

# Validate that a date is a real calendar date
is_valid_date() {
    local date_str="$1"

    # Try to parse the date using date command
    if date -d "$date_str" >/dev/null 2>&1; then
        return 0
    else
        return 1
    fi
}

# Convert date string to timestamp for comparison
date_to_timestamp() {
    local date_str="$1"
    date -d "$date_str" +%s 2>/dev/null || echo "0"
}

################################################################################
# Main Validation
################################################################################

echo "=========================================="
echo "Changelog Validation"
echo "=========================================="
echo "File: $CHANGELOG_FILE"
echo ""

# Check if file exists
if [[ ! -f "$CHANGELOG_FILE" ]]; then
    error "File not found: $CHANGELOG_FILE"
    exit 1
fi

# Read file into array
mapfile -t lines < "$CHANGELOG_FILE"
total_lines=${#lines[@]}

echo "Starting validation of $total_lines lines..."
echo ""

# Track state
line_num=0
previous_blank=false
in_version_section=false
version_line_num=0
last_date_timestamp=999999999999
last_date_str=""
expecting_blank=false
has_version=false

# Validate each line
for line_num in "${!lines[@]}"; do
    line="${lines[$line_num]}"
    actual_line_num=$((line_num + 1))

    # Line 1: Must be header
    if [[ $line_num -eq 0 ]]; then
        if [[ ! "$line" =~ ^\*\*\*\ .+\ \*\*\*$ ]]; then
            error "Line $actual_line_num: Header must be in format '*** Plugin Name ***'"
        else
            success "Valid header found"
        fi
        continue
    fi

    # Line 2: Must be blank
    if [[ $line_num -eq 1 ]]; then
        if [[ -n "$line" ]]; then
            error "Line $actual_line_num: Must be blank line after header"
        fi
        previous_blank=true
        continue
    fi

    # Check if line is blank
    if [[ -z "$line" ]]; then
        if $expecting_blank; then
            # This is expected
            expecting_blank=false
            previous_blank=true
            in_version_section=false
        elif $previous_blank; then
            error "Line $actual_line_num: Multiple consecutive blank lines found"
        else
            previous_blank=true
            in_version_section=false
        fi
        continue
    fi

    # Non-blank line
    if $expecting_blank; then
        error "Line $actual_line_num: Expected blank line before this line"
    fi

    # Check if this is a version line
    if [[ "$line" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}\ -\ version\ [0-9]+\.[0-9]+(\.[0-9]+)?.*$ ]]; then
        # This is a version line
        has_version=true

        # Must be preceded by blank line (except first version)
        if [[ $version_line_num -gt 0 ]] && [[ $previous_blank == false ]]; then
            error "Line $actual_line_num: Version line must be preceded by blank line"
        fi

        # Extract date
        date_str=$(echo "$line" | grep -oP '^\d{4}-\d{2}-\d{2}')

        # Validate date is real
        if ! is_valid_date "$date_str"; then
            error "Line $actual_line_num: Invalid date '$date_str' (not a real calendar date)"
        fi

        # Check chronological order (descending)
        if [[ $version_line_num -gt 0 ]]; then
            current_timestamp=$(date_to_timestamp "$date_str")
            if [[ $current_timestamp -gt $last_date_timestamp ]]; then
                error "Line $actual_line_num: Date '$date_str' is newer than previous date '$last_date_str' (versions must be in descending order)"
            fi
            last_date_timestamp=$current_timestamp
            last_date_str=$date_str
        else
            last_date_timestamp=$(date_to_timestamp "$date_str")
            last_date_str=$date_str
        fi

        in_version_section=true
        version_line_num=$actual_line_num
        previous_blank=false

    # Check if this is a list item
    elif [[ "$line" =~ ^\*\  ]]; then
        # This is a list item

        # Must be in a version section
        if [[ $version_line_num -eq 0 ]]; then
            error "Line $actual_line_num: List item found before any version declaration"
        fi

        # Check for special case: "* Initial release."
        if [[ "$line" == "* Initial release." ]]; then
            # This is valid
            previous_blank=false
            expecting_blank=true
            continue
        fi

        # Must follow pattern: * Keyword - Description
        if [[ "$line" =~ ^\*\ ([A-Za-z]+)\ -\  ]]; then
            # Extract keyword
            keyword=$(echo "$line" | sed -E 's/^\* ([A-Za-z]+) - .*/\1/')

            # Check if keyword is valid
            if ! is_valid_keyword "$keyword"; then
                error "Line $actual_line_num: Invalid keyword '$keyword' (not in allowed list)"
                echo "  Available keywords: Add, Added, Feature, New, Developer, Dev, Tweak, Changed, Update, Delete, Remove, Fixed, Fix"
            else
                # Check capitalization
                proper_cap=$(get_proper_capitalization "$keyword")
                if [[ "$keyword" != "$proper_cap" ]]; then
                    error "Line $actual_line_num: Keyword '$keyword' must be capitalized as '$proper_cap'"
                fi
            fi

            # Check exact spacing: * Keyword - Description
            if [[ ! "$line" =~ ^\*\ [A-Za-z]+\ -\  ]]; then
                error "Line $actual_line_num: Incorrect spacing. Must be: '* Keyword - Description' (asterisk, space, keyword, space, dash, space, description)"
            fi

        else
            # Does not match required pattern
            if [[ "$line" =~ ^\*\  ]]; then
                error "Line $actual_line_num: List item must follow format '* Keyword - Description' with exact spacing"
            fi
        fi

        previous_blank=false

    else
        # Unexpected line format
        error "Line $actual_line_num: Unexpected line format. Expected version line, list item, or blank line"
        previous_blank=false
    fi
done


# Check if at least one version was found
if [[ $has_version == false ]]; then
    error "No version entries found in changelog"
fi

# Print summary
echo ""
echo "=========================================="
echo "Validation Summary"
echo "=========================================="

if [[ $ERROR_COUNT -eq 0 ]]; then
    success "Changelog validation PASSED"
    echo ""
    exit 0
else
    echo -e "${RED}✗ Changelog validation FAILED${NC}"
    echo "Total errors: $ERROR_COUNT"
    echo ""
    exit 1
fi

