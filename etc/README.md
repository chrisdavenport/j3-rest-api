/etc
====
Here you will find a couple of JSON files used for configuration of the API.

Note that the API code also grabs configuration data from the regular Joomla configuration.php file.

## config.json
Contains general configuration data.

### absoluteHrefs
Set to true if link relation hrefs should be rendered as absolute instead of relative URLs.  Default is false.
If you want to use the HAL Browser then you will need to set this to true.

## services.json
Contains routes for services that are not provided by Joomla components.  Not currently used and contains dummy data.

