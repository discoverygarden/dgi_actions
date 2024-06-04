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
- Uses uuid by default as handle suffix. Also supports custom suffixes.
- Adds a unique constraint to the configured handle suffix field in an entity.
- Prevents changes to the suffix field of a node.
- Makes the suffix field non-editable if it has a value when editing a node.

## Usage
- See DGI Actions readme for general usage.
- Create a new service data type with the type `handle`.
- There is no UI for configuring the handle suffix field. It must be set in the
  configuration file. The default suffix field is `uuid`.
- Once the suffix field is set, the module will add a unique constraint to the
  field in the entity schema and make sure that the field value is not changed.

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
