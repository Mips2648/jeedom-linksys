# Plugin Linksys

Plugin allowing to control some aspects of your compatible Linksys routers.

What is available:

- Model and firmware
- Status of Guest network and Parental controls mode
- Number of devices connected to the router per type of connection
- Activate/Deactivate parental controls
- Activate/Deactivate guest network
- Reboot
- Activate/Deactivate router LEDs
- Control firmware upgrade
- WAN status

>**Important**
>This plugin has been tested with Linkys Velop VLP01 and firmware 1.1.13.202617. Will most probably work with others.

# Configuration

## Plugin configuration

The plugin **Linksys** does not require any specific configuration and should only be activated after installation.

The data is checked every 5 minutes.

## Equipment configuration

To access the different equipment **Linksys**, go to the menu **Plugins → Communication → Linksys**.

On the equipment page, fill in local router IP address, Admin login (usually 'admin'), and Admin password

# Credits

This plugin has been inspired by the work done by:

- [reujab](https://github.com/reujab)  through his JNAP Go library:  [linksys](https://github.com/reujab/linksys)
