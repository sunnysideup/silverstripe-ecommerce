## Silverstripe E-commerce ##

[![Build Status](https://travis-ci.org/sunnysideup/silverstripe-ecommerce.svg?branch=master)](https://travis-ci.org/sunnysideup/silverstripe-ecommerce)


## Need Help? ##

This Silverstripe Module is available for you free of charge.  In addition, I am happy to provide anyone who is keen to use it with
up to two hours of free support to get it working and/or ask some basic questions. Free support is available via e-mail and it may not
be available at all times of the year (just ask!). After that, support can be provided @ USD85 per hour (paid in advance).

## Can Help? ##

Of course, you are also welcome to provide any feedback or bug reports and so on.
Especially real user experience feedback is immensely valuable.
Developers who provide significant patches will be listed below.
Contributors are welcome.

## Requirements ##

see composer.json

## Project Home ##

 * See http://code.google.com/p/silverstripe-ecommerce
 * See http://www.silverstripe-ecommerce.com
 * https://github.com/sunnysideup/silverstripe-ecommerce

## Demo ##

See http://www.silverstripe-ecommerce.com


## Installation Instructions ##

There are no special tricks to install this module. Install like any
other Silverstripe Module module.


## Development Philosophy and Version Management ##

In contrast with most projects, the master is regularly chopped, altered, changed and improved.
This is not best practice, but it works for us.

While we regularly change the core, we try to keep API changes to a minimum and
we ensure that you can upgrade an e-commerce project from version 0.0001 to 99.0 without
much hassle.  For this purpose, we have written a build task that upgrades your database schema,
fixes data issues (e.g. adds defaults to new database fields), etc...

From time to time, we create a tag. This will usually coincide with a new version of Framework.
For example, when Framework tag 4.3.1 is released,
we may tag a version of the e-commercemaster as 4.3.1 at the same time.
From time to time, we also update the latest branch.

To make it work for you, in case you are working with the latest stable version of
the Silverstripe Framework, you just check out (fork) master, keep updating and working with it
until your projects is getting close to going live.  At that moment you lock e-commerce and
you only apply bug fixes where needed.

Next, when you get to update your clients Framework version you can choose to update e-commerce at the same time.
This will require you to retest the e-commerce functionality as we may have inadvertently broken something.

In terms of version numbering, we try to keep the tags and branch numbers synchronised with the
Silverstripe Framework versions.  This makes it easier to see what version is right for you
(e.g. ecommerce 3.1 should work well with the 3.1 branch of framework, etc...).


## Where the hell are the Unit Tests? ##

Right now, ecommerce does not have any php unit tests.
This is a major shortcoming that will be set right in due course.
We welcome contributions and/or donations to make this happen.


## Developers ##

Nicolaas Francken [at] sunnysideup.co.nz
Jeremy, Silverstripe Ltd, Romain, Toro, Shane, and many others.
