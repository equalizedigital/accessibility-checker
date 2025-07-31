# Contribution Guidelines

Before filing a bug report or a feature request, be sure to read the contribution guidelines.

## How to use GitHub
We use GitHub exclusively for well-documented bugs, feature requests and code contributions. Communication is always done in English.

To receive free support for Accessibility Checker we have the following channels:
* [The support pages on our site](https://my.equalizedigital.com/support/)
* [Support forums](https://wordpress.org/support/plugin/accessibility-checker/) on WordPress.org

Thanks for your understanding.

## Security issues
Please do not report security issues here. Instead, email them to security at our domain so we can deal with them securely and quickly.

## I have found a bug
Before opening a new issue, please:
* update to the newest versions of WordPress and the Accessibility Checker plugins.
* search for duplicate issues to prevent opening a duplicate issue. If there is already an open existing issue, please comment on that issue.
* follow our _New issue_ template when creating a new issue.
* add as much information as possible. For example: add screenshots, relevant links, step by step guides etc.

## I have a feature request
Before opening a new issue, please:
* search for duplicate issues to prevent opening a duplicate feature request. If there is already an open existing request, please leave a comment there.
* add as much information as possible. For example: give us a clear explanation of why you think the feature request is something we should consider for the plugin.

## I want to create a patch
Community made patches, localizations, bug reports and contributions are very welcome and help us tremendously.

When contributing please ensure you follow the guidelines below so that we can keep on top of things.

#### Submitting an issue you found
Make sure your problem does not exist as a ticket already by searching through [the existing issues](https://github.com/equalizedigital/accessibility-checker/issues). If you cannot find anything which resembles your problem, please [create a new issue](https://github.com/equalizedigital/accessibility-checker/issues/new).

#### Fixing an issue

* Fork the repository on GitHub (make sure to use the `develop` branch).
* Make the changes to your forked repository.
* Ensure you stick to the [WordPress Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/) and you properly document any new functions, actions and filters following the [documentation standards](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/php/).
* When committing, reference your issue and include a note about the fix.
* Push the changes to your fork and submit a pull request to the `develop` branch of the Accessibility Checker repository.

We will review your pull request and merge when everything is in order. We will help you to make sure the code complies with the standards described above.

### Automated Backport Process
When a pull request is merged into the `main` branch, an automated workflow will create a backport pull request to merge the same feature branch into the `develop` branch. This ensures that changes in `main` are also applied to the development branch without directly merging `main` into `develop`.

**How it works:**
- The workflow triggers automatically when a PR is merged into `main`
- It extracts the original branch name that was merged
- If the branch still exists, it creates a new PR to merge that branch into `develop`
- The backport PR is labeled with `backport` and `automated` labels
- If the branch no longer exists, the workflow logs a message indicating manual backport may be needed

**No action required** - this process is fully automated and requires no manual intervention in most cases.

#### 'Patch welcome' issues
Some issues are labeled 'patch-welcome'. This means we see the value in the particular enhancement being suggested but have decided for now not to prioritize it. If you however decide to write a patch for it, we'll gladly include it after some code review.

#### Additional Resources
* [General GitHub Documentation](https://help.github.com/)
* [GitHub Pull Request documentation](https://help.github.com/send-pull-requests/)
