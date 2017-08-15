# Quickbooks Online Connect UI #
**Contributors:**      Zao  
**Donate link:**       http://zao.is  
**Tags:**  
**Requires at least:** 4.7.0  
**Tested up to:**      4.7.3  
**Stable tag:**        0.2.6  
**License:**           GPLv2  
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html  

## Description

Provides UI for connecting to Quickbooks Online over OAuth. This plugin is a UI wrapper for [Quickbooks Online Connect](https://github.com/zao-web/qbo-connect).

## Installation

### Manual Installation

1. Upload the entire `/quickbooks-online-connect-ui` directory or clone this repository to the `/wp-content/plugins/` directory.
2. Run `composer install` inside the `/wp-content/plugins/quickbooks-online-connect-ui` directory.
3. Activate Quickbooks Online Connect UI through the 'Plugins' menu in WordPress.
4. Update the connection settings.

## Screenshots

1. Settings Page - Waiting for Client ID/Secret for connection
![Settings Page - Waiting for Client ID/Secret for connection](https://raw.githubusercontent.com/zao-web/quickbooks-online-connect-ui/master/waiting-for-connection.png)

2. After settings are saved, OAuth authorization begins with the QuickBooks server.
![After settings are saved, OAuth authorization begins with the QuickBooks server.](https://raw.githubusercontent.com/zao-web/quickbooks-online-connect-ui/master/authorizing.png)

3. Settings page after successful authentication
![Settings page after successful authentication](https://raw.githubusercontent.com/zao-web/quickbooks-online-connect-ui/master/connected.png)

4. Clicking "Check API Connection" fetches your QuickBooks company information to demonstrate a successful connection
![Clicking "Check API Connection" fetches your QuickBooks company information to demonstrate a successful connection](https://raw.githubusercontent.com/zao-web/quickbooks-online-connect-ui/master/check-api-connection.png)
