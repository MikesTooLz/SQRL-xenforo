# SQRL for XenForo

![SQRL logo](src/addons/Sqrl/icon.png)

This add-on adds SQRL authentication to XenForo 2.0. You can read more about SQRL from [Gibson Research Corporation](https://www.grc.com/sqrl/sqrl.htm).

## Requirements

- XenForo 2.0 using HTTPS
- PHP cURL extension [http://php.net/manual/en/book.curl.php]
- SSP server on the domain of the web server or a subdomain

## Install

- Upload everything inside `upload/` to the root of your XenForo install
- Navigate to the Admin CP -> Add-ons and click 'Install' on SQRL
- On the server hosting the SSP server, open `regedit.exe` and navigate to `Computer\HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Gibson Research\SqrlApi`
- Set `WebServerAuthURL` to this value `https://your-xenforo-install.com/the/xf/path/?sqrl/authenticate/&token=%s`
- Go to Setup -> Connected accounts -> SQRL
- Enter the hostname of the SSP server (as seen from the web browser's perspective)
- Enter the private hostname and port of the SSP server (as seen from the web server's perspective)
- Save

## Uninstall

- Navigate to Admin CP -> Add-ons and click 'Uninstall' on SQRL
- Delete these items
    - `js/sqrl.js`
    - `styles/default/sqrl`
    - `src/addons/Sqrl`
- Run this query in the database to delete all the identity tokens:
    - `DELETE FROM xf_user_connected_account WHERE provider = 'sqrl'`

You will also need to remove them from the SSP server which is not done automatically.
