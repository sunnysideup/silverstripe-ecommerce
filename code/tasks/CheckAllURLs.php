<?php

/**
 * @description (see $this->description)
 *
 * @authors: Andrew Pett [astuart.pett@gmail.com], Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 **/

class CheckAllURLs extends BuildTask {

	protected $title = 'Check URLs for HTTP errors';

	protected $description = "Will go through main URLs on the website, checking for HTTP errors (e.g. 404)";

	/**
	  * Prefix for all URLs.
	  * @default "http://localhost"
	  */
	private $prefix = "http://localhost";

	/**
	  * List of URLs to be checked. Excludes front end pages (Cart pages etc).
	  */
	private $urls = array(
	"/admin/shop/",
	"/admin/shop/EcommerceDBConfig/EditForm/field/EcommerceDBConfig/item/new",
	"/admin/shop/OrderStep",
	"/admin/shop/OrderStep/EditForm/field/OrderStep/item/new",
	"/admin/shop/EcommerceCountry",
	"/admin/shop/EcommerceCountry/EditForm/field/EcommerceCountry/item/new",
	"/admin/shop/OrderModifier_Descriptor",
	"/admin/shop/OrderModifier_Descriptor/EditForm/field/OrderModifier_Descriptor/item/new",
	"/admin/shop/EcommerceCurrency",
	"/admin/shop/EcommerceCurrency/EditForm/field/EcommerceCurrency/item/new",


	"/admin/pages/",
	"/admin/pages/add/",


	"/admin/products/",
	"/admin/products/Product/EditForm/field/Product/item/new",
	"/admin/products/ProductGroup",
	"/admin/products/ProductGroup/EditForm/field/ProductGroup/item/new",


	"/admin/sales/",
	"/admin/sales/Order/EditForm/field/Order/item/new",
	"/admin/sales/OrderStatusLog",
	"/admin/sales/OrderStatusLog/EditForm/field/OrderStatusLog/item/new",
	"/admin/sales/OrderItem",
	"/admin/sales/OrderItem/EditForm/field/OrderItem/item/new",
	"/admin/sales/OrderModifier",
	"/admin/sales/OrderModifier/EditForm/field/OrderModifier/item/new",
	"/admin/sales/OrderEmailRecord",
	"/admin/sales/OrderEmailRecord/EditForm/field/OrderEmailRecord/item/new",
	"/admin/sales/BillingAddress",
	"/admin/sales/BillingAddress/EditForm/field/BillingAddress/item/new",
	"/admin/sales/ShippingAddress",
	"/admin/sales/ShippingAddress/EditForm/field/ShippingAddress/item/new",


	"/admin/assets/",
	"/admin/assets/add/?ID=0",


	"/admin/reports/",
	"/admin/reports/SideReport_BrokenRedirectorPages/",
	"/admin/reports/SideReport_BrokenVirtualPages/",
	"/admin/reports/BrokenLinksReport/",
	"/admin/reports/SideReport_BrokenLinks/",
	"/admin/reports/SideReport_BrokenFiles/",
	"/admin/reports/SideReport_EmptyPages/",
	"/admin/reports/SideReport_RecentlyEdited/",
	"/admin/reports/EcommerceSideReport_AllProducts/",
	"/admin/reports/EcommerceSideReport_NoInternalIDProducts/",
	"/admin/reports/EcommerceSideReport_FeaturedProducts/",
	"/admin/reports/EcommerceSideReport_NoImageProducts/",
	"/admin/reports/EcommerceSideReport_NotForSale/",
	"/admin/reports/EcommerceSideReport_NoPriceProducts/",
	"/admin/reports/EcommerceSideReport_EcommercePages/",
	"/admin/reports/EcommerceSideReport_ProductsWithVariations/",


	"/admin/security/",


	"/admin/settings/",


	"/",
	"/about-us/",
	"/contact-us/",
	"/product-category/",
	"/product-category/?sortby=title",
	"/product-category/?sortby=price",


	"/cart/",
	"/checkout/",
	"/checkout/checkoutstep/orderformaddress/",
	"/checkout/checkoutstep/orderconfirmationandpayment/",
	"/product-category-2/");

	/**
	  * Pages to check by class name. For example, for "ClassPage", will check the first instance of the cart page.
	  */
	private $classnames = array(
		"CartPage",
		"CheckoutPage",
		"OrderConfirmationPage",
		"AccountPage",
		"Product",
		"ProductGroup",
		"ProductGroupSearchPage"
		);


	public function run($request) {
		set_time_limit(0);

		$prefix = $this->getPrefix();

		$this->prepareURLs();

		$username = "ECOMMERCE_URLCHECKER___";
		$password = rand(1000000000,9999999999);

		$Member = new Member();
		$Member->Email = $username;
		$Member->Password = $password;
		$Member->write();

		$Member->Groups()->add(Group::get()->filter(array("code" => "administrators"))->first());
		echo "Made member: ".$Member->Email."<br />";;
		echo "Logging in..<br />";

		$username = $Member->Email;
		$loginUrl = $prefix.'/Security/LoginForm';
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'Email='.$username.'&Password='.$password);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		 
		//execute the request (the login)
		$loginContent = curl_exec($ch);
		$loginCurlInfo = curl_getinfo($ch);
		if($loginCurlInfo['http_code'] != 200) {
			echo "<span style='color:red'>There was an error!</span><br />";
			
		} else {
			echo "<span style='color:green'>Successfully made contact with login form.</span><br />";
						

			$content = curl_exec($ch);
			$info = curl_getinfo($ch);		 

			
			echo "Processing URLs..\n";
			$urls = $this->urls;
			$errors = 0;
			echo "<ul>";
			foreach($urls as $url) {
				if(strlen(trim($url)) < 1) continue; //Checks for empty strings.

				$url = $prefix.$url;

				curl_setopt($ch, CURLOPT_URL, $url);
				$response = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				echo "<li ".($httpCode == 200 ? "style='color:green'" : ""). ">HTTPCODE [".$httpCode."]: ".$url. "</li>"	;
				if($httpCode != 200 ) {
					echo "<li style='color:red'>* Problem at [".$url."] : http code [".$httpCode."]</li>";
					$errors++;
				}				
			}
			curl_close($ch);
			echo "</ul>";
			if(!$errors) echo "<strong>No problems found.</strong><br /><br />";
			else echo "<strong>$errors errors found!";
			$Member->delete();
		}
	}
	public function getDescription() {
		return $this->description;
	}

	public function getTitle() {
		return $this->title;
	}
	/**
	  * Takes {@link #$classnames}, gets the URL of the first instance of it (will exclude extensions of the class) and
	  * appends to the {@link #$urls} list to be checked
	  */
	private function prepareURLs() {
		//first() will return null or the object
		foreach($this->classnames as $class) {
			$page = $class::get()->exclude(array("ClassName" => $this->arrayExcept($this->classnames, $class)))->first();
			if($page) {
				$url = $page->link();
				$this->urls[] = $url;
			}
		}	
	}
	/**
	  * Takes an array, takes one item out, and returns new array
	  * @param Array $array Array which will have an item taken out of it.
	  * @param - $exclusion Item to be taken out of $array
	  * @return Array New array.
	  */
	private function arrayExcept($array, $exclusion) {
		$newArray = $array;
		for($i = 0; $i < count($newArray); $i++) {
			if($newArray[$i] == $exclusion) unset($newArray[$i]);
		}
		return $newArray;
	}

	private function getPrefix() {
		if(!$this->prefix || strlen($this->prefix < 1)) return "http://localhost";
		else return $this->prefix;
	}
}