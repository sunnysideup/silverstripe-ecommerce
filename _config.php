<?php

/**
 * @description: copy the commented lines to your own mysite/_config.php file for editing...
 * Make sure that you save this file as UTF-8 to get the right encoding for currency symbols.
 *
 *
 **/



//NOTE - if you dont like these URLs then you can change them by adding another rule
// with a higher number.
Director::addRules(50, array(
	'shoppingcart/$Action/$ID/$OtherID/$Version' => 'ShoppingCart_Controller',
	'ecommercepayment/$Action/$ID/$OtherID' => 'EcommercePaymentController',
	'ecommercetemplatetest/$Action/$ID/$OtherID' => 'EcommerceTemplateTest',
	'ecommercebuyabledatalist/$Action/$ID/$OtherID' => 'BuyableSelectField_DataList',
	'api/ecommerce/v1' => 'EcommerceRestfulServer'
));
Object::add_extension("Member", "EcommerceRole");
Object::add_extension("Payment", "EcommercePayment");
Object::add_extension("SiteTree", "EcommerceSiteTreeExtension");
Object::add_extension("Page_Controller", "EcommerceSiteTreeExtension_Controller");
Object::add_extension("DevelopmentAdmin", "EcommerceDevelopmentAdminDecorator");
Object::add_extension('Money', 'EcommerceMoney');
DevelopmentAdmin::$allowed_actions[] = 'ecommerce';

SS_Report::register("SideReport", "EcommerceSideReport_EcommercePages");
SS_Report::register("SideReport", "EcommerceSideReport_FeaturedProducts");
SS_Report::register("SideReport", "EcommerceSideReport_AllProducts");
SS_Report::register("SideReport", "EcommerceSideReport_NoImageProducts");
SS_Report::register("SideReport", "EcommerceSideReport_NoInternalIDProducts");
SS_Report::register("SideReport", "EcommerceSideReport_NoPriceProducts");
SS_Report::register("SideReport", "EcommerceSideReport_NotForSale");

//Object::useCustomClass('Currency','CurrencyImprovements', true);

// copy the lines below to your mysite/_config.php file and set as required.
// __________________________________START ECOMMERCE MODULE CONFIG __________________________________
//The configuration below allows you to customise your ecommerce application -
//Check for the defalt value first rather than setting eveery single config as this requires a lot
//of valuable processing where in many cases the default value is fine.

// * * * DEFINITELY MUST SET
//EcommerceConfig::set_folder_and_file_locations(array("ecommerce/_config/ecommerce.yaml"));


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

// __________________________________ END ECOMMERCE MODULE CONFIG __________________________________




// __________________________________ START PAYMENT MODULE CONFIG __________________________________
//MUST SET!
//Payment::set_site_currency("NZD");
//Payment::set_supported_methods(array('PayPalPayment' => 'Paypal Payment'));
// __________________________________ END PAYMENT MODULE CONFIG __________________________________



// * * * HIGHLY RECOMMENDED SETTINGS NON-ECOMMERCE
//Geoip::$default_country_code = 'NZ';
//Email::setAdminEmail("cool@bool.com");
