# DGI Actions Handle Constraints Module

## Introduction

This module is part of the DGI Actions Handle package. It provides functionality to add unique constraints to certain fields of an entity and handles the preservation of these fields during entity save operations.

## Notes

* This module should only be installed when a field is being used as a suffix field.
* The developer or site administrator should note that this module would make the suffix field always required and unique.

## Requirements

This module requires the following modules/libraries:

* [DGI Actions Handle](https://github.com/discoverygarden/dgi_actions_handle)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Features

1. **Unique Constraints**: The module can add a unique constraint to specified fields of an entity.

2. **Entity Base Field Alteration**: The module implements the `hook_entity_base_field_info_alter` hook to add constraints to the configured fields.

3. **Entity Bundle Field Alteration**: The module implements the `hook_entity_bundle_field_info_alter` hook to add constraints to the configured fields.

4. **Entity Pre-save**: The module implements the `hook_entity_presave` hook to revert the value of the suffix field if it is changed. This is needed for spreadsheet ingest.

5. **Form Alteration**: The module implements the `hook_form_alter` hook to disable the suffix/identifier fields that are not allowed to be changed.

## Usage

This module is used as part of the DGI Actions Handle package.
It is used to ensure that the fields that are used as suffixes for the Handle are unique and required. The module also ensures that the suffix field is not changed during entity save operations.
Once the configuration is set up, the module will handle the rest.

## Configuration

The module uses the `dgi_actions_handle_constraints.settings` configuration, which should be set up with the appropriate constraint settings.
For each field that is used as a suffix or identifier, a new value should be added to the constraint_settings array. A default configuration file which
uses the field_pid as suffix and field_handle as identifier is provided with the module. This can be updated with the appropriate field names.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[Developers](http://islandora.ca/developers) section on Islandora.ca and
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
