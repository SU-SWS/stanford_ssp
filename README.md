# [Stanford Simple SAML PHP](https://github.com/SU-SWS/stanford_ssp)
##### Version: 8.x-2.x

[![CircleCI](https://circleci.com/gh/SU-SWS/stanford_ssp.svg?style=svg)](https://circleci.com/gh/SU-SWS/stanford_ssp)
[![Maintainability](https://api.codeclimate.com/v1/badges/d597c026202dc075d677/maintainability)](https://codeclimate.com/github/SU-SWS/stanford_ssp/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/d597c026202dc075d677/test_coverage)](https://codeclimate.com/github/SU-SWS/stanford_ssp/test_coverage)

Maintainers: [jbickar](https://github.com/jbickar),  [sherakama](https://github.com/sherakama), [pookmish](https://github.com/pookmish)

[Changelog.txt](CHANGELOG.txt)

Additional enhancements to the Drupal contrib module [SimpleSamlPHP Auth](https://www.drupal.org/project/simplesamlphp_auth). See the contrib module for more documentation.

Installation
---

Follow installation guide provided by [SimpleSamlPHP Auth](https://www.drupal.org/project/simplesamlphp_auth)

Prerequisites
---

SimpleSAMLphp - you must have SimpleSAMLphp version 1.6 or newer installed and configured to operate as a service provider (SP).

See more at the documentaion for [SimpleSamlPHP Auth](https://www.drupal.org/project/simplesamlphp_auth)

Configuration
---

The main configuration page can be found at: `/admin/config/people/simplesamlphp_auth`

To use the workgroup API, you must work with the MAIS team to get a valid certificate. V1 API certificates do not automatically
work with the V2 API.

Troubleshooting
---

Send a helpsu to Stanford Web Services or post an issue to the GitHub issue queue.

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)

[![CircleCI](https://circleci.com/gh/SU-SWS/stanford_ssp/tree/8.x-1.x.svg?style=svg)](https://circleci.com/gh/SU-SWS/stanford_ssp/tree/8.x-1.x)


Releases
---

Steps to build a new release:
- Checkout the latest commit from the `8.x-2.x` branch.
- Create a new branch for the release.
- Commit any necessary changes to the release branch.
  -  These may include, but are not necessarily limited to:
    - Update the version in any `info.yml` files, including in any submodules.
    - Update the CHANGELOG to reflect the changes made in the new release.
- Make a PR to merge your release branch into `master`
- Give the PR a semver-compliant label, e.g., (`patch`, `minor`, `major`).  This may happen automatically via Github actions (if a labeler action is configured).
- When the PR is merged to `master`, a new tag will be created automatically, bumping the version by the semver label.
- The github action is built from: [semver-release-action](https://github.com/K-Phoen/semver-release-action), and further documentation is available there.
