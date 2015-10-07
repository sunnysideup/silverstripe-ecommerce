## Silverstripe E-commerce ##

[![Build Status](https://travis-ci.org/sunnysideup/silverstripe-ecommerce.svg?branch=master)](https://travis-ci.org/sunnysideup/silverstripe-ecommerce)


## Need Help? ##

This Silverstripe Module is available for you free of charge.  In addition, I am happy to provide anyone who is keen to use
this module  with up to two hours of free support to get it working and/or ask some basic questions.

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

To use SSL one some pages only, you can define:

DEFINE('_SECURE_URL', "https://www.mysite.co.nz");
DEFINE('_STANDARD_URL', "http://www.mysite.co.nz");

## Development Philosophy and Version Management ##

We are in the process of moving towards semantic versioning.

## Features ##

 * 40+ sub-modules available (varying quality levels, but plenty of inspiration and example code ;-))

 * extensive dev tools:
  - bundled under dev/ecommerce/ (e.g. www.mysite.co.nz/dev/ecommerce/
  - full list of all configs (~70) available through wizard like interface
  - install complete test site within two minutes (as per previous e-mail)
  - example front-end template showing nearly all variables available and how to use them
  - javascript code included (e.g. ajaxifies cart)
  - basic customisation guide: http://www.silverstripe-ecommerce.com/home/customisation-guide/
  - lots of helpful maintenance and debug scripts 
  - add /debug to the end of a product / product category URL (e.g. www.mysite.co.nz/my-product/debug/ to get all the coding info you may need for that object - e.g. SQL to select products in product category)

 * cart responses are easily customised / ajaxified and will update all identified areas on any page view

 * ability to use
  (a) no account (i.e. guest checkout),
  (b) must have account, or
  (c) account optional.
  This is one of the major e-com discussion points with clients. We have tried to cater for all three scenarios.

 * super flexible order process with customisable OrderModifiers and OrderSteps:

  - OrderModifiers add/deduct cost, products, etc... before placing the final order (e.g. tax, delivery, discounts)

  - OrderSteps do stuff after order is placed (e.g. email invoice, update stock, ping another API, etc... ) - kudos to Jeremy Shipman for promoting this concept

 * supports multi-currency

 * emails look great thanks to emogrifier e-mail formatting

 * we aim to have polished CMS views.

 * very flexible and powerful product categories:

  - extensive caching makes the product lists very fast, even with 1000s of products.

  - some smart features such as "previous" and "next" product links (based on last list shown),

  - built-in search, with ability to search within category, previous search, or entire product range

  - one product can be in many product categories

  - extensive filter and sort options (customisable through yml)

 * ability to use RESTFUL service API for connecting with third-party software

 * ability to create country specific pricing (requires custom code)

 * we are starting to use semantic versioning

 * can use Omnipay (code can be made available), although there are also a number of other payment gateways available (DPS, Paypal, etc...)

 * we are super happy to help anyone to get started with version of e-commerce

 * we would welcome financial support and other help to add
  (a) translations 
  (b) test suite


## Now you mention it ... where the hell are the Unit Tests? ##

Right now, ecommerce does not have any php unit tests.
This is a major shortcoming that will be set right in due course.
We welcome contributions and/or donations to make this happen.


## Developers ##

Nicolaas Francken [at] sunnysideup.co.nz
Jeremy, Silverstripe Ltd, Toro, Shane, and many others.
