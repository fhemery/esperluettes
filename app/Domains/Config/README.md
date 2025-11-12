# Config module

This module is in charge of handling configuration parameters, and is most accesible by admins and tech admins.

Currently, the config module is in charge of handling the following types of parameters:
- Feature toggles: enables to activate or deactivate features on a role basis

## Usage from other modules

To use the config module from other modules, you can use the [ConfigPublicApi](./Public/Api/ConfigPublicApi.php) class, in particular the `isToggleEnabled` method.