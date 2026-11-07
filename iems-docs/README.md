Zen Cart&reg; - The Art of E-Commerce
===============

Zen Cart&reg; was the first Open Source e-Commerce web application to be fully PA-DSS Certified.

Zen Cart v2.2.2 is the latest major update to the project.  It contains bugfixes and updates for PHP8 applied on top of the PA-DSS Certified version v1.5.4.

Zen Cart is free and open source software, with free community-driven support available 24/7 on the Zen Cart&reg; Support Site forums at [zen-cart.com/forum](https://www.zen-cart.com/forum.php)

--------------------

This fork of Zen Cart&reg; was specifically written for the Logistics Section at Indianapolis Emergency Medical Services, the emergency medical services agency providing 911 services to Indianapolis/Marion County, Indiana.  

Indianapolis EMS started using Zen Cart&reg; in either 2012 or 2013.  The system has maintained almost 100% uptime over the last 13 or 14 years.  When the system was first built, files were edited by hand and uploaded to a web server.  Development was tedious and time-consuming.  With the advent of version control via Git or Subversion, the IEMS Zen Cart&reg; project was placed under version control in 2019 with version 1.5.6.  Version 1.5.6 was sunset and pulled from production on November 6, 2026.  Development on versions 1.5.7 and 1.5.8 started in 2022 with active development ending in June of 2023 on version 1.5.8.  Development of the 2.2.2 fork began in June of 2026.

This fork of Zen Cart was specifically written to support the Indianapolis EMS Logistics Section and their mutual aid partners in Indiana Hospital Preparedness District Five.  Prior to the development of Zen Cart and the underlying inventory control and management system and processes, Logistics depended on a labor-intensive set of processes to manage the inventory and the workforce. 

In 2012 or 2013, the Zen Cart project was commissioned to fork the Zen Cart code to solve these problems.  Five custom Zen Cart installations were created.  From then until 2018, the installations were managed outside of version control.  With the release of v1.5.6, IEMS-specific code was created on a development branch and then pushed into five separate branches for each installation.  For redundancy, global changes were made in the same fashion with each individual installation holding the respective branches for the others.  Version 1.5.7 development started in 2022 with plans to upgrade to 1.5.8, but that work was stopped in 2023 and resumed in 2026. 

---------------------

IEMS Project Documentation
--------------------------
Project documentation for this fork lives in this folder (`iems-docs/`), kept outside upstream `docs/` (upstream Zen Cart content) so upstream merges do not collide. The root `README.md` is kept consistent with upstream Zen Cart so upstream merges stay clean; the IEMS-specific text (the fork description above and this section) lives only here. Where the files below disagree with this README, this README wins for IEMS matters.

| File | Purpose |
|------|---------|
| [`TODO.md`](TODO.md) | Live task state by owner; "Not building" list |
| [`DECISIONS.md`](DECISIONS.md) | Rulings and standing constraints, one place |
| [`HISTORY.md`](HISTORY.md) | Dated project history, reconstructed from git |
| [`handoffs/`](handoffs/) | Dated, never-edited handoff records ([`TEMPLATE.md`](handoffs/TEMPLATE.md)) |
| [`IEMS-CHANGES.md`](IEMS-CHANGES.md) | Per-version technical change log (fork vs upstream 2.2.2) |
| [`iems_data.md`](iems_data.md) | IEMS data definitions |
| [`AGENTS.md`](AGENTS.md), [`CONVENTIONS.md`](CONVENTIONS.md), [`CLAUDE.md`](CLAUDE.md) | Agent guidance, coding standards, Claude rules |

**One source of truth.** Decisions and standing constraints: `DECISIONS.md`. Live state: `TODO.md`. Version-level technical detail: `IEMS-CHANGES.md`. Timeline: `HISTORY.md`. Handoffs are records only and are not edited after sending; the 2026-07-16 handoff holds historical requirements, and where it conflicts with `DECISIONS.md` or `TODO.md`, those win. The system of record is git: entries cite commit hashes, and anything not verifiable from git or the files is marked **unverified**.

**Roles.** Formal role approval is unverified (see `DECISIONS.md` open questions). Jeremiah (jrcook416) is project owner/developer, approves changes, and commits and pushes manually. AI assistants (Claude, GitHub Copilot) propose plans, implement approved work and stage changes; they never auto-commit, push or deploy.

**Handoff naming.** `iems-docs/handoffs/YYYY-MM-DD-<from>-to-<to>-<topic>.md`, lowercase, kebab-case topic, `-2` for same-day duplicates. The pre-convention handoff was renamed with `git mv` (history preserved); content unchanged.

---------------------


Zen Cart&reg; v2.2.2
---------------------

Compatibility
-------------
Zen Cart v2.2.2 is designed for:
 * PHP 8.2 to PHP 8.5
 * MySQL 5.7.8+ or MariaDB 10.2.7+
 * Apache 2.4
 
Refer to [compatibility requirements](https://docs.zen-cart.com/user/first_steps/server_requirements/) for additional details.


Installation
------------

Installation is simple:

1. [Download Zen Cart](https://github.com/zencart/zencart/releases)
2. Ensure you check that the md5/sha1 hash of the Zip matches those publicly posted.
  * The sha1 values for verifying the zip files hosted at Github are displayed on the [Zen Cart&reg; website](https://www.zen-cart.com/) along with [instructions on how to verify the file using the hash values](https://docs.zen-cart.com/user/installing/validate_sha/).
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
__The [Implementation Guide](https://www.zen-cart.com/docs/implementation-guide-v200.pdf) document is provided to give detailed instructions on how to install and secure your site in accordance with PCI Compliance requirements.__ Whether your site "needs" PCI Compliance or not is up to you to decide, but you should still follow the documented principles to maximize your site's resilience against troublesome access attempted by any undesired/unauthorized visitors.


Documentation
-------------
Use your browser to open the [/docs/index.html](https://www.zen-cart.com/docs/index.html) page for links to release documentation and the [Implementation Guide](https://www.zen-cart.com/docs/).  A storeowner documentation repository also exists at [docs.zen-cart.com/user/](https://docs.zen-cart.com/user/). 

Developer Documentation
-----------------------
Developers wishing to contribute to the Zen Cart&reg; core code may fork the [zencart/zencart](https://github.com/zencart/zencart) repository on github and issue Pull Requests from their own feature branches.  Please see [CONTRIBUTING](../CONTRIBUTING.md). 

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

Subscribe to [Critical News Updates And Release Announcements](https://www.zen-cart.com/subscription.php?do=addsubscription&f=2)

&nbsp;  

<p>This project is supported by:</p>
<p>
  <a href="https://www.digitalocean.com/">
    <img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="201px">
  </a>
</p>

&nbsp;  

*&copy;Copyright 2003-2026, Zen Cart&reg;. All rights reserved.*

