# DGI Actions ARK Identifier

## Introduction

DGI Action ARK Indentifier Utilities, including:
* Actions to Mint/Delete ARK Persistent Identifiers.
* Utility functions for parsing text associated with the CDL EZID service an ARK identifiers.

## Requirements

This module requires the following modules/libraries:

* [DGI Actions](https://github.com/discoverygarden/dgi_actions)
* [DGI Actions EZID](https://github.com/discoverygarden/dgi_actions/modules/dgi_actions_ezid)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Implementation

This module houses the extended versions of the Identifier Actions implemented
for ARK Identifiers within the [CDL EZID service](https://ezid.cdlib.org/doc/apidoc.html).

The CDL EZID service expects and sends data in string of keyed values separated by colons
and separate key-value pairs separated by line breaks.

Identifiers minted within the service have an [Internal Metadata](https://ezid.cdlib.org/doc/apidoc.html#internal-metadata),
currently in this module, we're configuring:
 * '\_target' - The external URL of the object being minted.
 * '\_status' - Changing from 'public' to 'reserved', so it can be deleted.

The default data profile for ARK Identifiers is [ERC](https://ezid.cdlib.org/doc/apidoc.html#profile-erc).

IMPORTANT NOTE: In order for minted identifiers to be deleted in the service their '\_status'
must be set to 'reserved'.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module, please check out the helpful
[Documentation](https://github.com/Islandora/islandora/wiki#wiki-documentation-for-developers),
[CDL EZID Documentation](https://ezid.cdlib.org/doc/apidoc.html),
[Developers](http://islandora.ca/developers) section on Islandora.ca and
contact [discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
