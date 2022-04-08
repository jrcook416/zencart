Zen Cart&reg; v1.5.7d built for IEMS Logistics.
---------------------
This is an upgrade/clean install built for Indianapolis EMS Logistics.  
The previous version, 1.5.6c, was retired from service on March 16, 2022.
This document was last updated with code changes on 2022-04-05.

Installed Plugins and Modules
-----------------------
The following plugins or modules have been added to the 1.5.7d stock code:
1. Edit Orders 4.6.1 was installed on 2022-03-25.
2. Easy Populate 4 was installed on 2022-03-25.
3. Administrator Notes 1.0 was installed on 2022-03-26.
4. Super Orders 5.0.0 was installed on 2022-03-26.
5. Add Customers from Admin 3.0.0 was installed on 2022-03-26.
6. One Page Checkout 2.3.11 was installed on 2022-03-26.

Code Modifications  
---------------
1. IEMS specific functions have been added to the extra_functions folders on Core and Vector. 
* The unit_lookup() function will pull the current unit list from the unit table, load it into an associative array, and return the array for use in a select.  It will load the unit description in the 'id' and 'text' fields and the unit filter in the 'agency_filter' field. 
* The unit_lookup_filtered() function will pull the current agency list from the unit table, load it into an associative array, and return the array for use in a select. (Deprecated on 2022-04-05 - a filtering select was created using Javascript and PHP in the vector/customers.php file.)
* The iems_pull_down_menu() function will create a select based upon either unit_lookup() or unit_lookup_filtered(). 
2. The administrator/supply technician username (employee ID number) will show in updates to an order, just like in 1.5.6c.
3. Edit Orders 4.6.1 and Super Orders 5.0.0 code have been modified to allow for a select box for agency (company) and unit selection.
* Most of this modification is in /vector/includes/modules/edit_orders/eo_common_address_format.php. 
4. Javascript/AJAX code has been added to the /vector/customers.php file.
* The unit select will filter based upon the agency filter. (Requires Bootstrap-Select CDN link in the customers.php vector/javascript folder.)


Installation
------------

Installation is simple:

1. [![Download Zen Cart](https://a.fsdn.com/con/app/sf-download-button)](https://sourceforge.net/projects/zencart/files/latest/download)
2. Ensure you check that the md5/sha1 hash of the Zip matches those publicly posted.
  * The md5/sha1 values for verifying the zip files hosted at Sourceforge are displayed on the [Zen Cart&reg; website](https://www.zen-cart.com/) along with [instructions on how to verify the file using the hash values](https://docs.zen-cart.com/user/installing/installing_misc/#how-to-validate-the-integrity-of-a-downloaded-file-md5-or-sha1-checksums).
3. Unzip the downloaded zip file 
4. Everything inside the folder you unzipped needs to be uploaded to your webserver … for example, into your `public_html` or `www` or `html` folder (the folder will already exist on your webserver)
5. In your browser, enter the address to your site, such as: `www.example.com` (or if you uploaded it into another subdirectory such as `foldername` use `www.example.com/foldername`)
6. Rename the `/includes/dist-configure.php` and `/admin/includes/dist-configure.php` files to "`configure.php`" and make the files writable (so the install process can write your configuration information into them after you answer a few questions in the following steps).
7. Also make the `/cache` and `/logs` folders writable. (You will be prompted about making other folders writable during installation)
8. Follow the instructions that appear in your browser for installation. 

If some of the terms used in these brief instructions are things you don't understand, there is a much more detailed set of instructions in the [/docs/Implementation-Guide](https://www.zen-cart.com/docs/implementation-guide-v157.pdf) PDF.

Upgrading
---------
Recommended reading related to upgrading: https://docs.zen-cart.com/user/upgrading/


Guidance for Secure Installations
---------------------------------
__The [Implementation Guide](https://www.zen-cart.com/docs/implementation-guide-v157.pdf) document is provided to give detailed instructions on how to install and secure your site in accordance with PCI Compliance requirements.__ Whether your site "needs" PCI Compliance or not is up to you to decide, but you should still follow the documented principles to maximize your site's resilience against troublesome access attempted by any undesired/unauthorized visitors.


Documentation
-------------
Use your browser to open the [/docs/index.html](https://www.zen-cart.com/docs/index.html) page for links to release documentation and the [Implementation Guide](https://www.zen-cart.com/docs/implementation-guide-v157.pdf).  A storeowner documentation repository also exists at [docs.zen-cart.com/user/](https://docs.zen-cart.com/user/). 

Developer Documentation
-----------------------
Developers wishing to contribute to the Zen Cart&reg; core code may fork the [zencart/zencart](https://github.com/zencart/zencart) repository on github and issue Pull Requests from their own feature branches.  Please see [CONTRIBUTING](CONTRIBUTING.md). 

Visit [docs.zen-cart.com/dev/](https://docs.zen-cart.com/dev/) for guidance on issues relevant to developers. This documentation site is very new, but content will be added over time.  

Developers wishing to contribute documentation should fork [zencart/documentation](https://github.com/zencart/documentation) and contribute PRs.  Please see [CONTRIBUTING to documentation](https://github.com/zencart/documentation/blob/master/CONTRIBUTING.md).


Source
------

The Zen Cart source code is available at: https://github.com/zencart/zencart

Support
-------
For free support, visit our support site: https://www.zen-cart.com/forum.php

Follow Us
---------
For news and updates about Zen Cart&reg;, follow us on [Twitter](http://twitter.com/zencart) and [Facebook](http://facebook.com/zencart)

Sign up for our free [Newsletter](http://eepurl.com/bafnNj)

Subscribe to [Critical News Updates And Release Announcements](https://www.zen-cart.com/subscription.php?do=addsubscription&f=2)


&nbsp;  

*&copy;Copyright 2003-2021, Zen Cart&reg;. All rights reserved.*

>>>>>>> Update README.md
