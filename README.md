# radadi - Race Data Display

This is a tool to display start lists and result lists publicly in real time during orienteering events. It is written in PHP and JavaScript/jQuery.


## Supported data sources
Currently the only supported data source is a CSV export from the [OE2010](http://www.sportsoftware.de) event software.

Support for [IOF XML format](http://orienteering.org/resources/it/data-standard-3-0/) is planned in order to automatically import event and stage names. We also plan to support "speaker intermediate results" (with radio control data) in the future.


## Supported devices
The lists can be displayed on big TV screens using cheap hardware, such as Banana Pi single-board computers. No software other than a web browser is required on these devices.

Support for mobile devices is planned for a future release.


## How to use
1. Install a webserver with PHP on a Windows PC running OE2010 (xampp is recommended).
2. Clone the repository into the "htdocs" folder of the webserver.
3. Set the IP address of the PC to 192.168.1.5. If you use a different address, adapt js/radadi.js accordingly.
4. In server.php, configure the lists that each client should display, and set the event name and stage.
5. Export a start list from OE2010 into startlist.csv, or set up an automatic export of a preliminary result (by classes) into resultlist.csv
6. Point client devices to http://192.168.1.5/radadi to display the list. They will automatically paginate the data and update it at the end of each display cycle.

Hopefully there will soon be a GUI to configure client settings.


## Acknowledgement
This software is a work in progress. It has been successfully used at one international orienteering event so far (Velikden Cup 2016 in Bulgaria), showing information on four TV screens.


## Author
Martin Zoller
Email: radadi at spam dot zoller dot tv
