# [Stanford Simple SAML PHP](https://github.com/SU-SWS/stanford_ssp)

Maintainers: [jbickar](https://github.com/jbickar),  [sherakama](https://github.com/sherakama)

[Changelog.txt](CHANGELOG.txt)

Simple SAML PHP authentication module for Drupal websites. This module is intended to replace the Webauth module and should be used independently of it. This module makes it possible for Drupal to communicate with SAML or Shibboleth identity providers (IdP) for authenticating users.

Sub Modules
---

**[Stanford SAML Block](https://github.com/SU-SWS/stanford_ssp/tree/7.x-2.x/modules/stanford_saml_block)**
Provides a login block and context for the sitewide header region. Also, alters the user login form to provide both local and SUNet login.

**[Stanford SimpleSAMLPHP Authentication](https://github.com/SU-SWS/stanford_ssp/tree/7.x-2.x/modules/stanford_simplesamlphp_auth)**
A complete re-write of the simplesamlphp_auth contrib module from Drupal.org. For more information on to why please see this thread: https://www.drupal.org/node/2745089

Installation
---

Install this module like any other module. [See Drupal Documentation](https://drupal.org/documentation/install/modules-themes/modules-7)

**DO NOT** install the simplesamlphp_auth contrib module from Drupal.org as it conflicts with this module.

**DO NOT** install the webauth module with this module as they conflict.

Prerequisites
---

SimpleSAMLphp - you must have SimpleSAMLphp version 1.6 or newer installed and configured to operate as a service provider (SP).

Please see [simplesamlphp_auth modules configuration for more](https://github.com/SU-SWS/stanford_ssp/tree/7.x-2.x/modules/stanford_simplesamlphp_auth#prerequisites).

Configuration
---

The main configuration page can be found at: `/admin/config/stanford/stanford_ssp`

**General Configuration:**  
In this section you can enable and disable authentication through SAML or local Drupal accounts. Be sure to have an authentication scheme planned and the appropriate permissions set before configuring this section as it is possible to lock yourself out of the Drupal website.

**User Account Configuration**  
In this section you can control the behaviour of users authenticating with SAML. You can choose wether to automatically create an account when a user successfully authenticates with the IDP, to automatically prompt the end user for SAML log in if they hit a 403 access denied page, and if they are allowed to create a Drupal password.

**SAML Configuration**  
These are the configuration options to let your Drupal website communicate with SimpleSAMLPHP. In here you tell Drupal where SimpleSAMLPHP is installed and which properties of the response describe the user.

#### Role Mappings
`/admin/config/stanford/stanford_ssp/role-mappings`

Role mappings allow for Drupal administrators to automatically assign roles to users who authenticate through SimpleSAMLPHP. This can be useful to assigning groups of people to specific roles. To do this select a Drupal role that already exists from the drop down menu, discover and copy and paste the workgroup you want to grant the Drupal role to and press the Add Mapping button. Be sure to copy and paste the workgroup name exactly as it needs to be an exact match.

Users that successfully authenticate will automatically receive one or more roles as everyone who authenticates will receive the `SSO User` role. Additional roles may be added if the user is a 'Student', 'Faculty', 'Staff', or some other type of account.

##### Using the eduPersonEntitlement Attribute to Map Workgroups to Roles
The default mechanism to retrieve a user's Workgroup membership information is to get that from the `eduPersonEntitlement` SAML attribute. However, you **must** request that attribute be released to your SAML Service Provider (SP). See [https://uit.stanford.edu/service/saml/arp](https://uit.stanford.edu/service/saml/arp) for details.

##### Using the Workgroup API to Map Workgroups to Roles
An alternative mechanism to retrieve a user's Workgroup membership information is to get that from the Workgroup API. You will need to configure your Drupal site and server to connect; see [https://uit.stanford.edu/developers/apis/workgroup](https://uit.stanford.edu/developers/apis/workgroup) for details.


#### Login Block & Forms  
`/admin/config/stanford/stanford_ssp/login-block-forms`

In this section you can control the appearance of the login form found at `/user`

#### Authorizations
`/admin/config/stanford/stanford_ssp/authorizations`

In this section you can control who is allowed to authenticate using SAML. You may want to restrict this section to a specific set of SUNet ID's, a workgroup or two, or you may just want to leave it to the default setting of allowing anyone with a sunet id to be authenticated.

You may also modify the default behaviour of the Local Drupal accounts and prevent only specific roles or user ids to authenticate.

#### Add SSO User

Use this form to add user accounts that may authenticate with SAML. This is useful for adding users and granting them roles prior to their first log in.

Migrating from the WebAuth Module for Drupal (WMD) to Stanford SimpleSAMLphp (Stanford SSP)
---
There is a drush command (`drush stanford-ssp-migrate-wmd` or `drush sspwmd`) to help migrate from WMD to Stanford SimpleSAMLphp.

That command:
1. Enables the `stanford_ssp_block` module (to place the "SUNetID Login" block)
2. Transfers permissions from the "SUNet User" role to the "SSO User" role
3. If set, sets the destination for redirecting the user upon successful login
4. If WMD allowed local Drupal logins, configures Stanford SSP similarly
5. If the WMD login link text has been customized, sets that link text
6. If WMD has restrictions on which users or workgroups can log in, sets those options similarly in Stanford SSP
7. Updates the `authmap` table so that existing WMD users can log in with Stanford SSP
8. Adds the "SSO User" role to all existing WMD users
9. Converts WMD workgroup role mappings to Stanford SSP equivalents
10. Configures Stanford SSP so that users logging in with this module automatically get the "SUNet User" role
11. Disables and uninstalls WMD

#### The "SUNet User" role
By default, users logging in with Stanford SimpleSAMLphp do **not** get the "SUNet User" role. If you are upgrading a site from WMD to Stanford SSP, and you want users who log in to get the "SUNet User" role, you **must** run `drush sspwmd`.

Troubleshooting
---

Send a helpsu to Stanford Web Services or post an issue to the GitHub issue queue.

Contribution / Collaboration
---

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see [GitHub's article on fork, branch, and pull requests](https://help.github.com/articles/using-pull-requests)
