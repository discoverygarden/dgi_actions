# DGI Actions Handle

## Introduction

Provides integration with Handle.net for minting identifiers.

## Requirements

This module requires the following modules/libraries:

* [DGI Actions](https://github.com/discoverygarden/dgi_actions)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Features

- Provides Action Plugins for minting and deleting Handle.net Handles.
- Provides a service data type plugin for Handle.net Handles.
- Uses uuid by default as the Handle's suffix. Also supports custom suffixes.

## Usage
- See DGI Actions readme for general usage.
- Create a new service data type with the type `handle`.
- There is no UI for configuring the Handle's suffix field. It must be set in the
  configuration file. The default suffix field is `uuid`.

## User Notes

- The field that is to be used for the Handle's suffix must be made unique and required
  in the content type configuration so that the value does not change. If the field is not unique,
  Handle generation will fail for the duplicate value.
- The Handle also must be unique and should be non-editable.
- To make the suffix and Handle fields non-editable and required, an additional module is provided
  `dgi_actions_handle_constraints`. This module should be enabled and configured to make the necessary field validation changes.
- See dgi_actions_handle_constraints' [README](/modules/dgi_actions_handle_constraints/README.md) for more information.

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
