# Prompt: Generate a WordPress filter for rule slugs

Write a WordPress filter for the `edac_filter_register_rules` hook that restricts the rules to only those with a slug matching the provided input(s).

**Requirements:**
- The filter should return only rules whose `slug` matches one of the provided slugs.
- The filter must be copy-paste ready for use in a theme or plugin.
- The filter should be as concise as possible, using best practices for WordPress hooks and PHP.

**Input:**
- `slugs`: A string (single slug) or an array of strings (multiple slugs) to match against the `slug` property of each rule.

**Example usage:**
- If `slugs` is `'broken_skip_anchor_link'`, only that rule is checked.
- If `slugs` is `['broken_skip_anchor_link', 'another_rule']`, both rules are checked.

**Output:**
- A PHP code snippet that adds a filter to `edac_filter_register_rules` and restricts the rules to only those with a matching slug or slugs.

---

## Example Output

```php
add_filter(
    'edac_filter_register_rules',
    function ( $rules ) {
        $slugs = [ 'broken_skip_anchor_link', 'another_rule' ]; // <-- Replace or set dynamically
        if ( ! is_array( $slugs ) ) {
            $slugs = [ $slugs ];
        }
        return array_filter(
            $rules,
            function ( $rule ) use ( $slugs ) {
                return in_array( $rule['slug'], $slugs, true );
            }
        );
    }
);
```

