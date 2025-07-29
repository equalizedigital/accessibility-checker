#!/usr/bin/env bash

# You must have the `sed` on your system and `semver` package
# installed globally through npm (`npm install -g semver`) to
# use this script. It will fail otherwise.
#
# Usage: ./prep_release.sh (major|minor|patch)
#
set -e

if [[ -z "$1" ]]; then
  echo "Must supply a release type (major|minor|patch)"
  exit 1
fi

MAIN_FILE_PATH='accessibility-checker.php'
PACKAGE_JSON_PATH='package.json'
README_PATH='readme.txt'

echo "Getting plugin version from ${MAIN_FILE_PATH}"
VERSION=$(grep "Version:" ${MAIN_FILE_PATH} | sed -E 's/ \* Version: ([0-9]+\.[0-9]+\.[0-9]+).*/\1/')

echo "Current version: ${VERSION}"
BUMPED_VERSION=$(npx semver ${VERSION} -i ${1})
echo "Bumping to version: ${BUMPED_VERSION}"

if [[ -z "${VERSION}" || -z "${BUMPED_VERSION}" ]]; then
  echo "Failed to retrieve version from main file or semver could not bump the version";
  exit 1
fi

MAIN_BRANCH=main
DEVELOP_BRANCH=develop
RELEASE_BRANCH_NAME=release/${BUMPED_VERSION}

echo
echo "Creating release branch"
echo
git fetch
git checkout ${DEVELOP_BRANCH}
git pull
git checkout -b ${RELEASE_BRANCH_NAME}

echo
echo "Updating version in files"
echo
sed -i.bak -E "s/( \* Version:[[:space:]]*)[0-9]+\.[0-9]+\.[0-9]+/\1${BUMPED_VERSION}/g" "${MAIN_FILE_PATH}"
sed -i.bak -E "s/(define\( 'EDAC_VERSION', ')[0-9]+\.[0-9]+\.[0-9]+/\1${BUMPED_VERSION}/" "${MAIN_FILE_PATH}"
sed -i.bak -E "s/(\"version\": \")[0-9]+\.[0-9]+\.[0-9]+/\1${BUMPED_VERSION}/" package.json
sed -i.bak -E "s/(Stable tag: )[0-9]+\.[0-9]+\.[0-9]+/\1${BUMPED_VERSION}/" readme.txt

echo
echo "Updating WordPress version requirements"
echo

# Function to calculate WordPress version three versions back
calculate_wp_requires_version() {
    local tested_up_to=$(grep "Tested up to:" "${README_PATH}" | sed -E 's/Tested up to: ([0-9]+\.[0-9]+).*/\1/')
    
    if [[ -z "${tested_up_to}" ]]; then
        echo "Could not extract 'Tested up to' version from readme.txt" >&2
        return 1
    fi
    
    echo "Current 'Tested up to': ${tested_up_to}" >&2
    
    # Extract major and minor version numbers
    local major=$(echo "${tested_up_to}" | cut -d. -f1)
    local minor=$(echo "${tested_up_to}" | cut -d. -f2)
    
    # Calculate three versions back
    local target_minor=$((minor - 3))
    local target_major=${major}
    
    # Handle cases where we need to go back to previous major version
    if [[ ${target_minor} -lt 0 ]]; then
        target_major=$((major - 1))
        # WordPress typically has 10+ minor versions per major release
        # If we go negative, we'll use a reasonable estimate
        case ${major} in
            6)
                # WordPress 6.x series - estimate based on known releases
                target_minor=$((10 + target_minor))
                ;;
            *)
                # For other versions, use a conservative approach
                target_minor=$((9 + target_minor))
                ;;
        esac
        
        # Don't go below WordPress 5.0 (reasonable minimum)
        if [[ ${target_major} -lt 5 ]]; then
            target_major=5
            target_minor=0
        fi
    fi
    
    echo "${target_major}.${target_minor}"
}

# Calculate the new requires version
NEW_WP_REQUIRES=$(calculate_wp_requires_version)
if [[ $? -eq 0 && -n "${NEW_WP_REQUIRES}" ]]; then
    echo "Setting 'Requires at least' to: ${NEW_WP_REQUIRES}"
    sed -i.bak -E "s/(Requires at least: )[0-9]+\.[0-9]+/\1${NEW_WP_REQUIRES}/" readme.txt
    rm  "${MAIN_FILE_PATH}.bak" "${PACKAGE_JSON_PATH}.bak" "${README_PATH}.bak"
else
    echo "Warning: Could not calculate WordPress requires version, keeping current value"
    rm  "${MAIN_FILE_PATH}.bak" "${PACKAGE_JSON_PATH}.bak" "${README_PATH}.bak"
fi

echo
echo "Committing version bump"
echo
git add ${MAIN_FILE_PATH} ${PACKAGE_JSON_PATH} ${README_PATH}
git commit -m "Bump version ${VERSION} -> ${BUMPED_VERSION}"
git push -u origin ${RELEASE_BRANCH_NAME}

echo
echo "Creating changelog"
echo
git checkout ${MAIN_BRANCH}
git pull
git checkout ${RELEASE_BRANCH_NAME}

echo
echo "Changelog: "
echo
git shortlog ${MAIN_BRANCH}..${DEVELOP_BRANCH} --grep "Merge pull request" --format=%b | cat | \
    sed '/.*[rR]elease\/.*[0-9]\.[0-9]\.[0-9]/d' | \
    sed 's/ \{6\}/\- [ \] /'

echo
echo "Use the link below to open a PR for the release. Use the changelog above to write a proper changelog in an additional commit on the branch."
echo "https://github.com/equalizedigital/accessibility-checker/compare/${MAIN_BRANCH}...${RELEASE_BRANCH_NAME}?title=Release+v${BUMPED_VERSION}"

