<?php

/**
 * @description: copy the commented lines to your own mysite/_config.php file for editing...
 * Make sure that you save this file as UTF-8 to get the right encoding for currency symbols.
 *
 *
 **/


Member::add_extension("Member", "EcommerceRole");
SiteTree::add_extension("SiteTree", "EcommerceSiteTreeExtension");
Page_Controller::add_extension("Page_Controller", "EcommerceSiteTreeExtension_Controller");
DevelopmentAdmin::add_extension("DevelopmentAdmin", "EcommerceDevelopmentAdminDecorator");
Money::add_extension("Money", 'EcommerceMoney');
DevelopmentAdmin::$allowed_actions[] = 'ecommerce';

/*****************************************************
* REQUIRES: GeoIP,
******************************************************/


// copy the lines below to your mysite/_config.php file and set as required.
// __________________________________START ECOMMERCE MODULE CONFIG __________________________________
//The configuration below allows you to customise your ecommerce application -
//Check for the defalt value first rather than setting eveery single config as this requires a lot
//of valuable processing where in many cases the default value is fine.

// * * * DEFINITELY MUST SET
//EcommerceConfig::set_folder_and_file_locations(array("ecommerce/ecommerce_config/ecommerce.yaml"));

//customising the CMS
//LeftAndMain::require_css("ecommerce/css/ecommercecmsfixes.css");

// * * * ECOMMERCE I18N SETTINGS NOTES
// * * * for Currency &  Date Formats get this module: http://code.google.com/p/silverstripe-i18n-fieldtypes/
//Object::useCustomClass('Currency','I18nCurrency',true);
//Object::useCustomClass('Money','CustomMoney',true);
// * * * FOR DATE FORMATS SET F.E.
//setlocale (LC_TIME, 'en_NZ@dollar', 'en_NZ.UTF-8', 'en_NZ', 'nz', 'nz');
//Object::useCustomClass('SS_Datetime','I18nDatetime',true);
//OR
//i18n::set_locale('en_NZ');
//Object::useCustomClass('SS_Datetime','ZendDate',true);
//Currency::setCurrencySymbol("€");
//date_default_timezone_set("NZ");
//Object::useCustomClass('Currency','CurrencyImprovements', true);

// __________________________________ END ECOMMERCE MODULE CONFIG __________________________________







// * * * HIGHLY RECOMMENDED SETTINGS NON-ECOMMERCE
//Geoip::$default_country_code = 'NZ';
//Email::setAdminEmail("cool@bool.com");
