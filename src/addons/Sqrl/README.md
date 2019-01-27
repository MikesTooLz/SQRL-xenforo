# SQRL for XenForo

This addon adds SQRL authentication to XenForo 2.0.

## Requirements

- XenForo 2.0 using HTTPS
- PHP cURL extension [http://php.net/manual/en/book.curl.php]
- SSP server on the domain of the web server or on a subdomain

## Install

- Upload everything inside `upload/` to the root of your XenForo install
- Navigate to the Admin CP -> Add-ons and click 'Install'
- Go to Setup -> Connected accounts -> SQRL
- Enter the hostname of the SSP server. This is as seen from the web browser's perspective
- Enter the private hostname and port of the SSP server. This is as seem from the web server's perspective
- Save

## Uninstall

After uninstalling this add-on from XenForo using the add-on page in the Admin CP you will also need to clear out all people's access tokens. This is done the following way:

    DELETE FROM `xf_user_connected_account` WHERE `provider` = `sqrl`

You will also need to remove them from the SSP server which this add-on does not do automatically.
