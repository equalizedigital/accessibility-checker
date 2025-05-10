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
rm  "${MAIN_FILE_PATH}.bak" "${PACKAGE_JSON_PATH}.bak" "${README_PATH}.bak"

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

