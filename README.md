[![Download Zen Cart E-Commerce Shopping Cart ](https://img.shields.io/sourceforge/dm/zencart.svg)](https://sourceforge.net/projects/zencart/files/latest/download) ![](https://github.com/zencart/zencart/workflows/Zen%20Cart%20Tests/badge.svg?branch=v158)


Zen Cart&reg; - The Art of E-Commerce
===============

Zen Cart&reg; was the first Open Source e-Commerce web application to be fully PA-DSS Certified.

Zen Cart&reg; v1.5.8 is an update with several bugfix patches applied on top of the PA-DSS Certified version v1.5.4.

It's free software, with free community-driven support available 24/7 on the Zen Cart&reg; Support Site forums at [zen-cart.com/forum](https://www.zen-cart.com/forum.php)

---------------------
Zen Cart&reg; v158-dev-new for Indianapolis EMS Logistics
---------------------
Zen Cart branch v158-dev-new is a branch that holds work completed on the Indianapolis EMS Zen Cart&reg; v158 release.  It contains the following modifications:
1. An initial commit of the v158 stock code as referenced from upstream/v158.
2. Updated v158 stock code on 2022-12-01.
3. [ZCA Bootstrap Template v3.4.1](https://www.zen-cart.com/downloads.php?do=file&id=2191) on 2022-12-01.
4. [ImageHandler 5](https://www.zen-cart.com/downloads.php?do=file&id=2169) on 2022-12-01.
5. [One Page Checkout v2.4.4](https://www.zen-cart.com/downloads.php?do=file&id=2095) on 2022-12-01.
6. Updated vector/customers.php to handle the addition of county, unit, and agency identifiers to the customer record on 2023-01-09. 
7. Updated vector/includes/functions/extra_functions/iems.php to handle changes to the agency and county unit identifier query functions on 2023-01-09.
8. Updated includes/classes/Customer.php on 2023-01-09 to handle custom changes to the Customer class involving the address book on 2023-01-09.
9. Updated the MySQL schema with the addition of database tables for agency, county, and unit identifiers on 2023-01-10. 
10. Updated the README.md file with coding changes made between 2022-12-01 to 2023-01-10 on 2023-01-10. 
11. [Edit Orders v4.6.2](https://www.https://www.zen-cart.com/downloads.php?do=file&id=1513) on 2023-01-13.
12. [Administrator Notes v2](https://www.zen-cart.com/downloads.php?do=file&id=2339) on 2023-01-13.
13. [Database I/O Manager 1.6.7](https://www.zen-cart.com/downloads.php?do=file&id=2091) on 2023-01-13.
14. [SuperOrders v5.0.0 beta1](https://www.zen-cart.com/downloads.php?do=file&id=155&styleid=2) on 2023-01-13.
15. Updated multiple pieces of One Page Checkout to comply with IEMS-specific unit identification needs and processes during the week of 2023-01-16 to 2023-01-20 and the week of 2023-01-23 to 2023-01-24.  
16. Updated the README.md file with a brief summary of changes made between 2023-01-10 and 2023-01-24.
17. Continued work to get custom unit information into the order class and workflow.  I was successful in getting information pulled from the customer record to save to the order table.  The next priority will be to make sure that One Page Checkout is updating this unit information and saving that information, not the queried address information, to the order tables.  Following that, I will build a custom lookup to replace the unit ID number with a unit description in the administrative interface. 

----------------------
Work Completed
----------------------
Active development on the Indianapolis EMS Logistics build started on 2022-12-01, using stock v1.5.8 code from the GitHub repository.  Repository upstream/v158 was cloned to an Indianapolis EMS local machine for development. I completed the initial pull anticipating approximately six to nine months of development time between normal job duties and functions prior to the production release of v1.5.8 to our stakeholders.  This process was accelerated by the end of life of PHP 7.4 in December. 

There are specific modifications that Kevin Gona requested in this update.  
1. Users from one agency should not be able to see the list of ordering units in another agency.
2. The checkout process needs to be combined into one or two steps where possible. 
3. Focus on combining all sites into one site framework for ease of operation for the logistics technician and warehouse coordinator roles. 

I merged the ZCA Bootstrap Template, ImageHandler 5, and One Page Checkout files on December 1 and continued with normal business processes. I made some template adjustments to the Bootstrap 5 template along with some work on the IEMS-specific global function definitions and /vector/customers.php (which drives administrative maintenance on customer records in the system) on 2023-01-09. I continued work on these files, including JavaScript definition files, on 2023-01-10.  I started working on the Edit Orders tweaks on 2023-01-11, at which time I had to stop and look at changes to language file definition arrays in PHP 8.  This set me back a day while I cleaned up those files. I was given approval to continue with sole development on Zen Cart&reg; around 13 January and have focused my efforts on development since then. 

On 2023-01-13, I continued this work and installed several more plugins, including Super Orders 5. I started active work on One Page Checkout on 2023-01-16 and have worked on it exclusively for the last six business days. The work has been focused on combining the front-end workflow goals (users only being able to see units in their assigned agency AND combining the checkout process into one or two steps) and moving one step closer to production.

As of 2023-01-26 at end of shift, the next step will be to integrate custom Indianapolis EMS elements into the general Zen Cart&reg; order processing flow.  See note #17 for progress. 

----------------------
Compatibility
-------------
Zen Cart v1.5.8 is designed for:
 * PHP 7.3 to PHP 8.2
 * MySQL 5.7.8+ or MariaDB 10.2.7+
 * Apache 2.2 and 2.4
 
Refer to [compatibility requirements](https://docs.zen-cart.com/user/first_steps/server_requirements/) for additional details.


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

If some of the terms used in these brief instructions are things you don't understand, there is a much more detailed set of instructions in the [/docs/Implementation-Guide](https://www.zen-cart.com/docs/) PDF.

Upgrading
---------
Recommended reading related to upgrading: https://docs.zen-cart.com/user/upgrading/


Guidance for Secure Installations
---------------------------------
__The [Implementation Guide](https://www.zen-cart.com/docs/implementation-guide-v157.pdf) document is provided to give detailed instructions on how to install and secure your site in accordance with PCI Compliance requirements.__ Whether your site "needs" PCI Compliance or not is up to you to decide, but you should still follow the documented principles to maximize your site's resilience against troublesome access attempted by any undesired/unauthorized visitors.


Documentation
-------------
Use your browser to open the [/docs/index.html](https://www.zen-cart.com/docs/index.html) page for links to release documentation and the [Implementation Guide](https://www.zen-cart.com/docs/).  A storeowner documentation repository also exists at [docs.zen-cart.com/user/](https://docs.zen-cart.com/user/). 

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
For free community-driven support with Zen Cart, visit our support site: https://www.zen-cart.com/forum.php


Donations/Sponsorship
---------------------
Sponsorship through GitHub is a simple and convenient way to say "thank you" to Zen Cart's maintainers and contributors, and to help fund its ongoing development.

Just click the "Sponsor" button [on the Zen Cart page on GitHub](https://github.com/zencart/zencart). 

If your company uses Zen Cart, note that sponsorship and donations to the project are a valid regular business expense.

You may also donate via our website at https://www.zen-cart.com/donate


Security
--------
We take security very seriously.

If you have discovered a critical security bug in Zen Cart, please email security [at] zen-cart [.] com with the details of the problem and how to trigger it.  Issues will be responded to in a timely manner.


Follow Us
---------
For news and updates about Zen Cart&reg;, follow us on [Twitter](http://twitter.com/zencart) and [Facebook](http://facebook.com/zencart)

Sign up for our free [Newsletter](http://eepurl.com/bafnNj)

Subscribe to [Critical News Updates And Release Announcements](https://www.zen-cart.com/subscription.php?do=addsubscription&f=2)

&nbsp;  

<p>This project is supported by:</p>
<p>
  <a href="https://www.digitalocean.com/">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="201px">
  </a>
</p>

&nbsp;  

*&copy;Copyright 2003-2022, Zen Cart&reg;. All rights reserved.*

