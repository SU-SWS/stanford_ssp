#[Stanford Simple SAML PHP](https://github.com/SU-SWS/stanford_ssp)
##### Version: 7.x-1.x

Maintainers: [jbickar](https://github.com/jbickar), [sherakama](https://github.com/sherakama)

[Changelog.txt](CHANGELOG.txt)

Simple SAML PHP authentication module for Drupal websites.


Sub Modules
---

**[Stanford SAML Block](https://github.com/SU-SWS/stanford_ssp/modules/stanford_saml_block)**
Provides a login block and context for the sitewide header region. Also, alters the user login form to provide both local and SUNet login.

Installation
---

Install this module like any other module. [See Drupal Documentation](https://drupal.org/documentation/install/modules-themes/modules-7)

Download and install the simplesamlphp_auth contrib module and add the following patches:
  * https://www.drupal.org/files/issues/init-logout-notice-2717473-5.patch
  * https://www.drupal.org/files/issues/cookie-message-alert.patch

##TODO: Note about SAML and SIMPLESAML

Configuration
---

##TODO

Troubleshooting
---

Send a helpsu to Stanford Web Services.

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)
