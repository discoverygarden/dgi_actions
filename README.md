# DGI Actions

## Introduction

Provides a framework to support minting and deletion of identifiers either locally or with external services.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/Islandora/islandora)

## Installation

Install as usual, see
[this](https://www.drupal.org/docs/extending-drupal/installing-modules) for
further information.

## Configuration

This module and its submodules come with no configuration out of the box. Below
is the steps for configuring the included `dgi_actions_ark_identifier`.

### Entity configuration
The main configuration overview for all entities used within the module is
located at `admin/config/dgi_actions`.

#### Data profile configuration
Data profile entities contain data used when building up a request to a service. These values are retrieved from
the entity and are passed along with an HTTP request. [EZID](https://ezid.cdlib.org/doc/apidoc.html#metadata-profiles) provides a good example of how this is used.

1. Create a new data profile: `admin/config/dgi_actions/data_profile/add`.
2. Give the data profile a name.
3. Select the entity and bundle for which the data will be retrieved from. In this example choose `node` and `Repository Item`.
4. Select the `DataProfile` plugin that's being used. In this example choose `ERC`.
5. For the three `ERC` fields choose which fields to map from.
6. Save the data profile.

#### Service data configuration
Service data entities contain configuration used for interacting with external APIs.

1. Create a new service data: `admin/config/dgi_actions/service_data/add`.
2. Give the service data a name.
3. Select the `ServiceData` plugin that's being used. In this example choose `EZID`.
4. Fill in the required fields that is provided by the `EZID` plugin.
5. Save the service data.

#### Identifier configuration
Identifiers tie everything together. In the event `ServiceData` and `DataProfiles` are being used they
store references to the configured entities from above. Similarly, they store where the minted identifier is going to be placed.

1. Create a new identifier: `admin/config/dgi_actions/identifier/add`.
2. Give the identifier a name.
3. Select the entity and bundle for which the data will be stored on. In this example choose `node` and `Repository Item`.
4. Select the field in which the identifier will be stored in. For the example choose whichever field you want.
5. Choose the `ServiceData` being used for the request from the dropdown if needed. For the example choose the one created above.
6. Choose the `DataProfile` being used for the request from the dropdown if needed. For the example choose the one created above.
7. Save the identifier.

### Action configuration
An action is required for each identifier being minted and optionally deleted.

1. Create a new action: `admin/config/system/actions`.
2. Under `Create an advanced action` choose either the mint or delete action to be configured. For the example choose `Mint ARK EZID Identifier`.
3. Choose the identifier entity that the action will trigger and save.
4. Repeat the above and instead choose `Delete ARK EZID Identifier` and save.

### Context configuration
Drupal's Context module is used in conjunction with conditions and entity hooks to handle minting and deleting with a custom condition to check if a entity already has a persistent identifier.

1. Create a new context: `admin/structure/context/add`.
2. Give it a name and optionally fill out the other fields and save.
3. Configure the conditions required for an identifier to be minted. For the example create two conditions: `Node Bundle` and `Entity Has Persistent Identifier`. Configure the `Node Bundle` condition to look for the `Repository Item` bundle and `Content from hook`. Configure the `Entity Has Persistent Identifier` condition to use the `Identifier` created above and `Content from hook`. Negate this condition such that it will only mint if it does not already exist.
4. Choose require all conditions.
4. Add a reaction choose `Mints an identifier`.
5. Under `entity` choose whatever the mint action created above was called.
6. Repeat the above and instead choose the `Deletes an identifier` reaction and conditions that satsify deletion. Normally this would be just removing the negation on `Entity Has Persistent Identifier`.

## New Integrations

To create a new identifier minting integration at least a `MintIdentifier` action is required.

Optionally a `DataProfile` plugin, a `ServiceDataType` plugin and a `DeleteIdentifer` action can be created if required.

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
