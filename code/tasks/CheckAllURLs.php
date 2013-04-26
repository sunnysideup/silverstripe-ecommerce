<?php

/**
 * @description (see $this->description)
 *
 * @authors: Andrew Pett [at] sunny side up .co.nz, Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 **/

class CheckAllURLs extends BuildTask {

	protected $title = 'Check URLs for HTTP errors';

	protected $description = "Will go through main URLs on the website, checking for HTTP errors (e.g. 404)";

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
	"/");

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

		$classURLs = $this->prepareClasses();
		$username = "ECOMMERCE_URLCHECKER___";
		$password = rand(1000000000,9999999999);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		//Test Class pages
		echo "<h4>Testing class pages (i.e. CartPage) before logging in</h4><br /><ul>";
		$errors = $this->testURLs($classURLs, $ch); // Test the class pages i.e. CartPage before logging in.
		echo "</ul>";
		echo "<strong><span" . ( $errors > 0 ? " style='color:red'" : " style='color:green'").">".$errors." errors.</span></strong><br /><br />";

		//Make temporary admin member
		echo "<strong>Making admin member (".$username.") on the fly...</strong><br />";
		$adminMember = Member::get()->filter(array("Email" => $username))->first();
		if($adminMember != NULL) { 
			echo "<strong>Member alread exists... deleting</strong><br />";
			$adminMember->delete();
		}
		$Member = new Member();
		$Member->Email = $username;
		$Member->Password = $password;
		$Member->write();
		$Member->Groups()->add(Group::get()->filter(array("code" => "administrators"))->first());
		
		echo "Made admin<br />";;
		echo "Logging in..<br />";

		$username = $Member->Email;
		$loginUrl = Director::absoluteURL('/Security/LoginForm');
		$ch = $this->login($ch, $loginUrl, $username, $password); // Will return 'false' if we failed to log in.
		if(!$ch) {
			echo "<span style='color:red'>There was an error logging in!</span><br />";
			
		} else {
			echo "<span style='color:green'>Successfully made contact with login form.</span><br />";
			echo "<h4>Retrying class pages after login.</h4><ul>";
			$errors = $this->testURLs($classURLs, $ch);
			echo "</ul>";
			echo "<strong><span" . ( $errors > 0 ? " style='color:red'" : " style='color:green'").">".$errors." errors.</span></strong><br /><br />";


			// Will add /admin/edit/show/$ID for each of the {@link #classnames} to {@link #urls}
			$this->array_push_array($this->urls, $this->prepareClasses(1));

			echo "<h4>Testing admin URLs</h4><ul>";
			$errors = $this->testURLs($this->urls, $ch); 
			echo "</ul>";
			echo "<strong><span" . ( $errors > 0 ? " style='color:red'" : " style='color:green'").">".$errors." errors.</span></strong><br /><br />";

			
			curl_close($ch);
			
		}
		$Member->delete();
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
	private function prepareClasses($publicOrAdmin = 0) {
		//first() will return null or the object
		$return = array();
		foreach($this->classnames as $class) {
			$page = $class::get()->exclude(array("ClassName" => $this->arrayExcept($this->classnames, $class)))->first();
			if($page) {
				if(!$publicOrAdmin) $url = $page->link();
				else $url = "/admin/pages/edit/show/".$page->ID;
				$return[] = $url;
			}
		}
		return $return;
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

	/**
	  * Will try log in to SS with given username and password.
	  * @param Curl Handle $ch A curl handle to use (will be returned later if successful).
	  * @param String $loginUrl URL of the form to post to
	  * @param String $username Username
	  * @param String $password Password
	  * @return Curl Handle|Boolean Returns the curl handle if successfully contacted log in form, else 'false'
	  */
	private function login($ch, $loginUrl, $username, $password) {
		curl_setopt($ch, CURLOPT_URL, $loginUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'Email='.$username.'&Password='.$password);
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		
		 
		//execute the request (the login)
		$loginContent = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode == 200) return $ch;
		return false;
	}

	/**
	  * Tests the URLs for a 200 HTTP code.
	  * @param Array(String) $urls an array of urls (relative to base site e.g. /admin) to test
	  * @param Curl Handle Curl handle to use
	  * @return Int number of errors
	  */
	private function testURLs($urls, $ch) {
		$errors = 0;
		foreach($urls as $url) {
			if(strlen(trim($url)) < 1) continue; //Checks for empty strings.

			$url = Director::absoluteURL($url);

			curl_setopt($ch, CURLOPT_URL, $url);
			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			echo "<li ".($httpCode == 200 ? "style='color:green'" : ""). ">HTTPCODE [".$httpCode."]: ".$url. "</li>"	;
			if($httpCode != 200 ) {
				echo "<li style='color:red'>* Problem at [".$url."] : http code [".$httpCode."]</li>";
				$errors++;
			}				
		}
		return $errors;
	}

	/**
	  * Pushes an array of items to an array
	  * @param Array $array Array to push items to (will overwrite)
	  * @param Array $pushArray Array of items to push to $array.
	  */
	private function array_push_array(&$array, $pushArray) {
		foreach($pushArray as $pushItem) {
			array_push($array, $pushItem);
		}
	}
}