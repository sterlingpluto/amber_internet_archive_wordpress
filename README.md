This is a fork of the original Amber Wordpress plugin mainly geared towards the current (2022) Internet Archive API.  This one checks if a queued link has a been saved using the Internet Archive availibility API (https://archive.org/help/wayback_api.php).  If a cached copy is found, it saves the archived link to the database. If not, then it submits a request for archive.org to save a copy which was how the old Internet Archive backend code saved items to the archive.  Unfortunately, the internet archive does not instantly return a response when things are submitted through its save endpoint, and the availibility API does not always show recently saved items so the database of archive links will have to be periodically refreshed.  The Amber dashboard page has also been updated with bulk actions (delete, force re-save) for convenience.  Force re-save tells Amber to treat a URL as "new" and to perform the entire archiving process whether or not it has been done before or if the site is currently marked as "down".
All other Amber back ends and functionality are left unchanged.  

Known Issues:
=================
* URL's that are currently accessible and return 200 HTTP code with "vanilla" PHP cURL requests sometimes return other codes like 301 through AmberNetworkUtils' code. 
* Archive requests sometimes hang.
* Cron job functionality not tested
* Code is possibly not secure.  Do not use in production environments!!!

# Original Readme: #
[![Build Status](https://travis-ci.org/berkmancenter/amber_wordpress.png?branch=wordpress)](https://travis-ci.org/berkmancenter/amber_wordpress)

Amber WordPress plugin
=================


Amber keeps links working on blogs and websites.

Whether links fail because of DDoS attacks, censorship, or just plain old link rot, reliably accessing linked content is a problem for Internet users everywhere. The more routes we provide to information, the more all people can freely share that information, even in the face of filtering or blockages. Amber adds to these routes.

## System Requirements ##

* WordPress 4.0 or higher (developed and tested on a Wordpress 5.8.3 installation)
* PHP cURL extension enabled

## Installation ##

For full installation instructions, as well as a guide for configurations and settings, see our Wiki on Github: https://github.com/berkmancenter/amber_wordpress/wiki

## Help! ##
The Berkman Klein Center's devs are happy to help you get Amber up and running. Contact us directly with questions: amber@cyber.law.harvard.edu.
