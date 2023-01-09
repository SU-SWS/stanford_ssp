# Stanford SSP

8.2.6
--------------------------------------------------------------------------------
_Release Date: 2023-01-09_

- Expand the existing user lookup to prevent unwanted user duplication (#134)

8.2.5
--------------------------------------------------------------------------------
_Release Date: 2022-07-08_

- Adjust test to eliminate deprecation message
- fixed composer namespace to lowercase
- Dont allow role mapping to authenticated role
- Replace deprecated functions (#128)
- Removed D8 Tests


8.x-2.4
--------------------------------------------------------------------------------
_Release Date: 2021-12-08_

- Revert su_display name_later hook. (#124)


8.x-2.3
--------------------------------------------------------------------------------
_Release Date: 2021-11-19_

- Use the display name field if it is populated (#122)


8.x-2.2
--------------------------------------------------------------------------------
_Release Date: 2021-09-03

- Fixed the destination path after saml login (#120)

8.x-2.1
--------------------------------------------------------------------------------
_Release Date: 2021-06-11_

- D8CORE-4339 Allow restriction based on the person's affiliation to the university (#118) (221e7a2)

8.x-2.0
--------------------------------------------------------------------------------
_Release Date: 2021-05-07_

- D8CORE-4129: changed labels for new sso user buttons (#115) (a66f0a8)
- D8CORE-000: Check for attribute existence on user login (#114) (979ff4d)
- D8CORE-4083 Use API V2 for the new workgroup API (#111) (6465c96)
- 2.x (0305353)

8.x-2.x
--------------------------------------------------------------------------------
- Replaced Workgroup API from V1 to V2
  - V1 used XML, V2 uses JSON
  - Workgroup API url has been removed from the config. It can now be configured in settings.php files with `$settings['stanford_ssp.workgroup_api'] = 'http://foo.bar';'`
  - Workgoup API certificates for V1 do not automatically work for V2. Database updates check for a successful connection and will error out if the certificates are not valid.

8.x-1.7
--------------------------------------------------------------------------------
_Release Date: 2021-04-09_

- Removed random unwanted space character (#107)
- D8CORE-3953 Refactor to replace "whitelist" with "allowed" in config
- HSD8-1027 Pass query parameters after loggin in via saml

8.x-1.6
--------------------------------------------------------------------------------
_Release Date: 2020-12-04_

- Adjusted tests to pass on D9 (#99) (a9f5a8a)
- removed core version for test module (dab9222)
- phpunit void return annoation (bd0c5e6)
- D9 Ready (#98) (2dbf7b6)

8.x-1.5
--------------------------------------------------------------------------------
_Release Date: 2020-09-09_

- D8CORE-2499: Updated composer license (#96) (beda642)
- D8CORE-000: Removed behat tests in favor of codeception.

8.1.4
--------------------------------------------------------------------------------
_Release Date: 2020-04-16_

* 8.1.4 (d8fec1e)
* D8CORE-1644: Dev branch workflow (1877202)
* D8CORE-1545 Added validation to check for public workgroups (#89) (a0acd1d)* D8CORE-1644: Dev branch workflow (1877202)
* D8CORE-1545 Added validation to check for public workgroups (#89) (a0acd1d)* D8CORE-1644: Dev branch workflow (1877202)
* D8CORE-1545 Added validation to check for public workgroups (#89) (a0acd1d)

8.x-1.3
--------------------------------------------------------------------------------
_Release Date: 2020-03-20_

- D8CORE-1511: Change role mapping for stanford_ssp from itservices:webservices to uit:sws (#86)

8.x-1.2
--------------------------------------------------------------------------------
_Release Date: 2020-02-05_

- Added a redirection route from /sso/login to /saml_login
- Fix role mapping form ajax (D8CORE-1243)
- Adjusted login screen to improve portal style (D8CORE-1024)
- Fixed config form (D8CORE-1263)

8.x-1.1
--------------------------------------------------------------------------------
_Release Date: 2019-10-30_

- Fixed core requirements info.yml

8.x-1.0
--------------------------------------------------------------------------------
_Release Date: 2019-10-30_

- Initial Release
