# DGI Actions

## Introduction

DGI Action Utilities, including:
* Actions to Mint/Delete Persistent Identifiers.
* Context Condition for if an Identifier field is or is not populated.
* Context Reactions for Entity Insert and Entity Delete.
* Method to execute Entity Reactions.
* Utility functions to get a list of configured identifiers.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/Islandora/islandora)

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Implementation

In order to leverage Identifier Minting and Deleting for a given identifier, the following configs are required:
* Identifier Config - dgi_actions.identifier.type.yml
  * Contains the identifiers main information.
  * Ex. dgi_actions.identifier.ark.yml
* Identifier Service Data Config - dgi_actions.service_data.type.yml
  * Contains the service data for specified identifier service.
  * Ex. dgi_actions.service_data.ark_ezid.yml
* Identifier Data Profile - dgi_actions.data_profile.type.yml
  * Contains the data layout for the specified data profile type.
  * Ex. dgi_actions.data_profile.erc.yml

Once configured, the applicable Mint or Delete class will need to be extended
to implement Identifier specific abstracted functions.

Once the extended classes have been implemented, the Actions will need to be
enabled and configured in the UI at '/admin/config/system/actions' after that,
a Context will have to be created at '/admin/structure/context' configured using
either the 'Mints an identifier' or 'Deletes an identifier' Reaction targeting the applicable Action.

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
