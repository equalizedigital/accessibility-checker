#!/bin/bash
# Array of code snippets
codes=(
    "edac_before_validate"
    "edac_after_get_content"
    "edac_before_rule"
    "edac_after_rule"
    "edac_after_validate"
    "edac_rule_errors"
    "edac_no_verify_ssl"
    "edac_filter_simplified_summary_heading"
    "edac_filter_post_types"
    "edac_filter_settings_capability"
    "edac_filter_register_rules"
    "edac_ignore_permission"
    "edac_filter_readability_content"
    "edac_filter_dashboard_widget_capability"
    "edac_filter_password_protected_notice_text"
    "edac_filter_insert_rule_data"
    "edac_debug_information"
    "edac_settings_tab_content"
    "edac_filter_settings_tab_items"
)

# Fetch all tags from the remote repository
git fetch --tags

# Loop through each code snippet
for code in "${codes[@]}"; do
    # Find the commit where this code was added
    commit=$(git log --source --all -S "$code" --pretty=format:"%H" | tail -1)

    # Check if the commit is part of any tag
    if git describe --tags "$commit" >/dev/null 2>&1; then
        # Find the earliest tag that includes this commit
        tag=$(git describe --tags "$commit")
        echo "The earliest tag that includes the code snippet '$code' is: $tag"
    else
        # If the commit is not part of any tag, get the oldest tag as per semver
        tag=$(git tag | sort -V | head -1)
        echo "The commit for the code snippet '$code' is not part of any tag. The oldest tag as per semver is: $tag"
    fi
done
