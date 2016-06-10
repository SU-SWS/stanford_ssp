core = 7.x
api = 2

; 7.x-3.x branch
projects[simplesamlphp_auth][type] = "module"
projects[simplesamlphp_auth][subdir] = "contrib"
projects[simplesamlphp_auth][download][type] = "git"
projects[simplesamlphp_auth][download][url] = "http://git.drupal.org/project/simplesamlphp_auth.git"
projects[simplesamlphp_auth][download][revision] = "6e92be67b007e4e9f4a071b9a82c7db8fbcee2af"

; https://www.drupal.org/node/2745089 | User registration and loading options.
projects[simplesamlphp_auth][patch][] = https://www.drupal.org/files/issues/user-registration-process_6.patch
