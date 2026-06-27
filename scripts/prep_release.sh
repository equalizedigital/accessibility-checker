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

# Ensure local tags are fully in sync with remote tags before release prep.
verify_local_tags_match_remote() {
  local local_tags_file
  local remote_tags_file

  local_tags_file=$(mktemp)
  remote_tags_file=$(mktemp)

  # Always clean up temporary files before returning.
  trap 'rm -f "${local_tags_file}" "${remote_tags_file}"' RETURN

  # --refs avoids peeled annotated-tag entries (^{}) for clean one-line refs.
  git show-ref --tags | awk '{print $1" "$2}' | sort > "${local_tags_file}"
  git ls-remote --tags --refs origin | awk '{print $1" "$2}' | sort > "${remote_tags_file}"

  if ! diff -u "${remote_tags_file}" "${local_tags_file}" >/dev/null; then
    echo
    echo "Error: local tags do not match tags on origin."
    echo "Please sync tags before running release prep."
    echo
    diff -u "${remote_tags_file}" "${local_tags_file}" || true
    return 1
  fi

  echo "Tag sync check passed: local tags match origin."
  return 0
}

echo
echo "Creating release branch"
echo
git fetch origin --tags --prune-tags
verify_local_tags_match_remote
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

# Function to fetch WordPress versions and calculate required version
update_wp_requires_version() {
    echo "Fetching WordPress version list..."

    # Get current "Tested up to" version from readme.txt
    local tested_up_to=$(grep "Tested up to:" "${README_PATH}" | sed -E 's/Tested up to: ([0-9]+\.[0-9]+).*/\1/')

    if [[ -z "${tested_up_to}" ]]; then
        echo "Error: Could not extract 'Tested up to' version from readme.txt" >&2
        return 1
    fi

    echo "Current 'Tested up to': ${tested_up_to}"

    # Try to fetch WordPress versions from the API first
    local wp_versions_json=$(curl -s --max-time 10 "https://api.wordpress.org/core/version-check/1.7/" 2>/dev/null || echo "")

    if [[ -n "${wp_versions_json}" ]]; then
        echo "Using WordPress API for version data..."
        # Parse JSON response to get version list in reverse chronological order
        # Extract major.minor versions from major.minor.patch format
        local versions_list=$(echo "${wp_versions_json}" | \
            grep -o '"version":"[0-9]*\.[0-9]*\.[0-9]*"' | \
            sed 's/"version":"\([0-9]*\.[0-9]*\)\.[0-9]*"/\1/' | \
            sort -rV | \
            uniq)
    else
        echo "API unavailable, trying to parse releases page..."
        # Fallback: try to parse the releases page
        local releases_html=$(curl -s --max-time 15 "https://wordpress.org/download/releases/" 2>/dev/null || echo "")

        if [[ -n "${releases_html}" ]]; then
            # Extract version numbers from the releases page
            # Look for patterns like "WordPress 6.8" or "wordpress-6.7.zip"
            local versions_list=$(echo "${releases_html}" | \
                grep -oE '(WordPress [0-9]+\.[0-9]+|wordpress-[0-9]+\.[0-9]+)' | \
                sed -E 's/(WordPress |wordpress-)([0-9]+\.[0-9]+)/\2/' | \
                sort -rV | \
                uniq)
        else
            echo "Error: Could not fetch WordPress version data from any source" >&2
            return 1
        fi
    fi

    if [[ -z "${versions_list}" ]]; then
        echo "Error: No WordPress versions found in response" >&2
        return 1
    fi

    echo "Found WordPress versions (latest first):"
    echo "${versions_list}" | head -10

    # Find the current tested version in the list and go back 2 versions (to support last 3 versions)
    local found_current=false
    local version_count=0
    local target_version=""

    while IFS= read -r version; do
        if [[ "${found_current}" == true ]]; then
            version_count=$((version_count + 1))
            if [[ ${version_count} -eq 2 ]]; then
                target_version="${version}"
                break
            fi
        elif [[ "${version}" == "${tested_up_to}" ]]; then
            found_current=true
            echo "Found current tested version ${tested_up_to} in version list"
        fi
    done <<< "${versions_list}"

    if [[ "${found_current}" == false ]]; then
        echo "Warning: Current 'Tested up to' version ${tested_up_to} not found in WordPress version list" >&2
        echo "Available versions: $(echo "${versions_list}" | tr '\n' ' ')" >&2
        return 1
    fi

    if [[ -z "${target_version}" ]]; then
        echo "Warning: Could not find version 2 releases back from ${tested_up_to}" >&2
        # Try to use the oldest version we found if we don't have enough history
        target_version=$(echo "${versions_list}" | tail -1)
        if [[ -n "${target_version}" ]]; then
            echo "Using oldest available version: ${target_version}"
        else
            return 1
        fi
    fi

    # Ensure we don't go below WordPress 5.0 (reasonable minimum)
    local min_major=$(echo "${target_version}" | cut -d. -f1)
    if [[ ${min_major} -lt 5 ]]; then
        target_version="5.0"
        echo "Adjusted to minimum supported version: ${target_version}"
    fi

    echo "Setting 'Requires at least' to: ${target_version} (2 versions back from ${tested_up_to} to support last 3 versions)"

    # Update the readme.txt file
    sed -i.bak -E "s/(Requires at least: )[0-9]+\.[0-9]+/\1${target_version}/" "${README_PATH}"

    return 0
}

# Update WordPress version requirements
if update_wp_requires_version; then
    echo "Successfully updated WordPress version requirements"
else
    echo "Warning: Could not update WordPress version requirements, keeping current values"
fi

rm  "${MAIN_FILE_PATH}.bak" "${PACKAGE_JSON_PATH}.bak" "${README_PATH}.bak"

echo
echo "Committing version bump"
echo
git add ${MAIN_FILE_PATH} ${PACKAGE_JSON_PATH} ${README_PATH}
git commit -m "Bump version ${VERSION} -> ${BUMPED_VERSION}"

echo
echo "Updating @since placeholder tags to ${BUMPED_VERSION}"
echo

# Stash any existing uncommitted work (tracked modifications + untracked files)
# so that the subsequent git diff picks up only what the tool changes.
STASH_MESSAGE="prep_release: pre-since-tag stash"
git stash push --include-untracked -m "${STASH_MESSAGE}"
STASH_CREATED=$?

# Ensure the stash is always restored, even if the script exits early.
restore_stash() {
  if [[ ${STASH_CREATED} -eq 0 ]]; then
    echo
    echo "Restoring stashed changes"
    echo
    git stash pop
  fi
}
trap restore_stash EXIT

php tools/update-since-tags.php --version="${BUMPED_VERSION}" --changed-since-last-tag

# Stage only the tracked PHP files that were modified by the tool.
# git diff --name-only only lists tracked files with unstaged changes,
# so untracked files are never included.
SINCE_CHANGES=$(git diff --name-only -- '*.php')
if [[ -n "${SINCE_CHANGES}" ]]; then
  echo
  echo "Committing @since tag updates"
  echo
  echo "${SINCE_CHANGES}" | xargs git add --
  git commit -m "Update @since placeholders to ${BUMPED_VERSION}"
else
  echo "No @since placeholder tags found — skipping commit."
fi

# Restore stashed work now (trap will also fire on EXIT, guard against double-pop).
restore_stash
trap - EXIT

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

