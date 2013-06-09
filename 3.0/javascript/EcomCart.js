/**
  * @description: update Cart using AJAX (JSON data source)
  * as well as making any "add to cart" and "remove from cart" links
  * work with AJAX (if setup correctly)
  * @author nicolaas @ sunny side up . co . nz
  *
  * in short, the way this works is that a bunch of items on the page
  * are set up to interact with the shopping cart:
  * - country selector
  * - region selector
  * - add to cart buttons
  * - remove from cart buttons
  *
  * If any of these form fields / buttons are clicked / changed
  * data is requested from the server.
  *
  * When the data returns, it is processed and its 'instruction'
  * are applied. Instructions can be:
  *
  * type = "id" | "class"
  * 	parameter = innerHTML => update innerHTML
  * 	parameter = hide => show/hide, using "hideForNow" class
  *  	parameter = anything else =>  update attribute
  * WITH name:
  *  - update attribute (e.g. update value for quantity field using the field's name)
  * WITH dropdownArray
  *  - update dropdown
  * WITH rows:
  *  - add / delete TO BE IMPLEMENTED
  *
  *
  * It is recommended that you adjust the IDs / class names / names / dropdown identifiers
  * in your html rather than trying to change what is being returned (although this is possible too).
  *
  * To see what is returns can be done as follows:
  * 1. log in with administrator credentials
  * 2. browse to:
  * @http://www.yoursite.com/shoppingcart/test/
  *
  * NOTE: for your own ajax needs, you can also JUST access the cart, like this:
  * http://www.yourseite.com/shoppingcart/showcart/
  *
  **/

(function($){
	$(document).ready(
		function() {
			EcomCart.init();
		}
	);
})(jQuery);

EcomCart = {

	/**
	 * Set to TRUE to see debug info.
	 * @var Boolean
	 */
	debug: false,
		set_debug: function(b) {this.debug = b;},

	/**
	 * selector to identify input field for selecting country.
	 */
	shoppingCartURLSegment: "shoppingcart",
		set_shoppingCartURLSegment: function(s) {this.shoppingCartURLSegment = s;},


	//#################################
	// COUNTRY + REGION SELECTION
	//#################################


	/**
	 * selector to identify the area in which the country + region selection takes place
	 * @todo: can we make this more specific?
	 */
	countryAndRegionRootSelector: "body",
		set_countryAndRegionRootSelector: function(s) {this.countryAndRegionRootSelector = s;},


	/**
	 * selector to identify input field for selecting country.
	 */
	ajaxCountryFieldSelector: "select.ajaxCountryField",
		set_ajaxCountryFieldSelector: function(s) {this.ajaxCountryFieldSelector = s;},

	/**
	 * selector to identify input field for selecting region.
	 */
	ajaxRegionFieldSelector: "select.ajaxRegionField",
		set_ajaxRegionFieldSelector: function(s) {this.ajaxRegionFieldSelector = s;},

	/**
	 * the selector of the main country field used to select the country of sale
	 */
	selectorMainCountryField: "#Country",
		set_selectorMainCountryField: function(s) {this.selectorMainCountryField = s;},


	//#################################
	// UPDATING THE CART - CLASSES USED
	//#################################

	/**
	 * class used to show cart data is being updated.
	 */
	classToShowLoading: "loading",
		set_classToShowLoading: function(s) {this.classToShowLoading = s;},

	/**
	 * class used to 'lock' the page while cart updates are being processed.
	 */
	classToShowPageIsUpdating: "ecomCartIsUpdating",
		set_classToShowPageIsUpdating: function(s) {this.classToShowPageIsUpdating = s;},


	/**
	 * this is a collection of dom elements that hold the item causing the change
	 * we retain this here so that we can add a loading class to it and,
	 * on return, we can remove it.
	 * Because it is an array, each clicked element can be individually given
	 * the loading class and also removed when its particular request returns.
	 */
	loadingSelectors: [],

	/**
	 * tells us the number of ajax calls that are currently awaiting
	 * processing
	 */
	openAjaxCalls: 0,

	/**
	 * Tells us if we are currently processing
	 *@var Boolean
	 */
	processing: true,

	/**
	 * the class used to show add/remove buyable buttons
	 */
	showClass: "show",
		set_showClass: function(s) {this.showClass = s;},

	/**
	 * the class used to hide add/remove buyable buttons
	 */
	hideClass: "hide",
		set_hideClass: function(s) {this.hideClass = s;},


	/**
	 * a method called before the update
	 * params for onBeforeUpdate:
	 * url, params, EcomCart.setChanges
	 * EcomCart.set_onBeforeUpdate(function(url, params, setChanges) {alert("before");});
	 */
	onBeforeUpdate: null,
		set_onBeforeUpdate: function(f) {this.onBeforeUpdate = f;},

	/**
	 * a method called after the update
	 * params for onAfterUpdate:
	 * changes, status
	 * EcomCart.set_onAfterUpdate(function(change, status) {alert("after");});
	 */
	onAfterUpdate: null,
		set_onAfterUpdate: function(f) {this.onAfterUpdate = f;},

	/**
	 * @var Array
	 * Synonyms are used in the update to also update
	 * They take the form of:
	 * Selector (e.g. MyCart) => Other Selectors
	 * It updates the Other Selectors at the same time as it updates the Selector
	 * e.g. Order_DB_302_Total => ".TotalAmounts"
	 * As most of the core selctors are dynamic, they should be set at runtime.
	 */
	synonyms: [],
		set_synonyms: function(a){this.synonyms = a;},
		add_synonym: function(key, value){this.synonyms[key] = value;},
		remove_synonym: function(key){this.synonyms.splice(key, 1);},

	//#################################
	// ITEMS (OR LACK OF) IN THE CART
	//#################################

	/**
	 * selector of the dom element shown when there are no items in cart.
	 */
	selectorShowOnZeroItems: ".showOnZeroItems",
		set_selectorShowOnZeroItems: function(s) {this.selectorShowOnZeroItems = s;},

	/**
	 * selector of the dom element that is hidden on zero items.
	 */
	selectorHideOnZeroItems: ".hideOnZeroItems",
		set_selectorHideOnZeroItems: function(s) {this.selectorHideOnZeroItems = s;},

	/**
	 * selector for the item rows.
	 */
	selectorItemRows: "tr.orderitem",
		set_selectorItemRows: function(s) {this.selectorItemRows = s;},

	/**
	 * the selector used to identify "remove from cart" links within the cart.
	 */
	removeCartSelector: ".ajaxRemoveFromCart",
		set_removeCartSelector: function(s) {this.removeCartSelector = s;},


	//#################################
	// AJAX CART LINKS OUTSIDE THE CART
	//#################################


	/**
	 * turn on / off the ajax buttons outside of the cart (e.g. add this product to cart, delete from cart)
	 */
	ajaxButtonsOn: true,
		set_ajaxButtonsOn: function(b) {this.ajaxButtonsOn = b;},

	/**
	 * NOTE: set to empty string to bypass confirmation step
	 */
	confirmDeleteText: 'Are you sure you would like to remove this item from your cart?',
		set_confirmDeleteText: function(s) {this.confirmDeleteText = s;},

	/**
	 * the area in which the ajax links can be found.
	 */
	ajaxLinksAreaSelector: "body",
		set_ajaxLinksAreaSelector: function(v) {this.ajaxLinksAreaSelector = v;},

	/**
	 * the selector used to identify links that add buyables to the cart
	 */
	addLinkSelector: ".ajaxBuyableAdd",
		set_addLinkSelector: function(s) {this.addLinkSelector = s;},

	/**
	 * the selector used to identify links that remove buyables from the cart
	 * (outside the cart itself)
	 */
	removeLinkSelector: ".ajaxBuyableRemove",
		set_removeLinkSelector: function(s) {this.removeLinkSelector = s;},

	/**
	 * the selector used to identify any buyable holder within a cart
	 */
	orderItemHolderSelector: ".orderItemHolder",
		set_orderItemHolderSelector: function(s) {this.removeLinkSelector = s;},



	//#################################
	// DIALOGUE POP-UP BOX
	//#################################

	/**
	 * the selector used to identify any links that open a pop-up dialogue
	 * the syntax is as follows:
	 * <a href="#colorboxDialogCart" class="colorboxDialog" rel="">show cart</a>
	 * <div id="colorboxDialogCart">content for pop-up</div> (this line is optional)
	 */
	colorboxDialogSelector: ".colorboxDialog",
		set_colorboxDialogSelector: function(s) {this.colorboxDialogSelector = s;},

	/**
	 * The options set for the colorbox dialogue, see: https://github.com/jackmoore/colorbox
	 * @var Int
	 */
	colorboxDialogOptions: {
		width: "500",
		height: "500",
		maxHeight: "90%",
		maxWidth: "90%",
		loadingClass: "loading",
		iframe: true,
		onOpen: function (event) {
			EcomCart.reinit();
		}
	},
		set_colorboxDialogOptions: function(o){this.colorboxDialogOptions = o;},



	//#################################
	// INIT AND RESET FUNCTIONS
	//#################################

	/**
	 * initialises all the ajax functionality
	 */
	init: function () {
		//make sure that country and region changes are applied to Shopping Cart
		EcomCart.countryAndRegionUpdates();
		//setup an area where the user can change their country / region
		EcomCart.changeCountryFieldSwap();
		if(EcomCart.ajaxButtonsOn) {
			//make sure that "add to cart" links are updated with AJAX
			EcomCart.addAddLinks(EcomCart.ajaxLinksAreaSelector);
			//make sure that "remove from cart" links are updated with AJAX
			EcomCart.addRemoveLinks(EcomCart.ajaxLinksAreaSelector);
			//make sure that "delete from cart" links are updated with AJAX - looking at the actual cart itself.
			EcomCart.addCartRemove(EcomCart.ajaxLinksAreaSelector);
		}
		EcomCart.reinit();
	},

	/**
	 * runs everytime the cart is updated
	 */
	reinit: function(){
		//hide or show "zero items" information
		EcomCart.updateForZeroVSOneOrMoreRows();
		EcomCart.initColorboxDialog();
		if (typeof EcomQuantityField  != 'undefined') {
			EcomQuantityField.init();
		}
		this.processing = false;
	},



	//#################################
	// COUNTRY AND REGION CHANGES
	//#################################

	/**
	 * sets the functions for updating country and region
	 */
	countryAndRegionUpdates: function() {
		jQuery(EcomCart.countryAndRegionRootSelector).delegate(
			EcomCart.ajaxCountryFieldSelector,
			"change",
			function() {
				var url = jQuery('base').attr('href') + EcomCart.shoppingCartURLSegment + "/setcountry/" + this.value + "/";
				EcomCart.getChanges(url, null, this);
			}
		);
		jQuery(EcomCart.countryAndRegionRootSelector).delegate(
			EcomCart.ajaxRegionFieldSelector,
			"change",
			function() {
				var url = jQuery('base').attr('href')  + EcomCart.shoppingCartURLSegment + "/setregion/" + this.value + "/";
				EcomCart.getChanges(url, null, this);
			}
		);
	},


	/**
	 * gets the options from the main country field and presents them as options for the user
	 * to select a new country.
	 */
	changeCountryFieldSwap: function() {
		jQuery(EcomCart.countryAndRegionRootSelector).delegate(
			EcomCart.selectorChangeCountryFieldHolder + " select",
			"change",
			function() {
				var val = jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").val();
				jQuery(EcomCart.ajaxCountryFieldSelector).val(val);
				var url = jQuery('base').attr('href') + EcomCart.shoppingCartURLSegment + "/setcountry/" + val + "/";
				EcomCart.getChanges(url, null, this);
				jQuery(EcomCart.selectorChangeCountryLink).click();
			}
		);
	},



	//#################################
	// SETUP PAGE
	//#################################

	/**
	 * adds the "add to cart" ajax functionality to links.
	 * @param String withinSelector: area where these links can be found, the more specific the better (faster)
	 */
	addAddLinks: function(withinSelector) {
		jQuery(withinSelector).delegate(
			EcomCart.addLinkSelector,
			"click",
			function(){
				var url = jQuery(this).attr("href");
				EcomCart.getChanges(url, null, this);
				return false;
			}
		);
	},

	/**
	 * adds the "remove from cart" ajax functionality to links.
	 * @param String withinSelector: area where these links can be found, the more specific the better (faster)
	 */
	addCartRemove: function (withinSelector) {
		jQuery(withinSelector).delegate(
			EcomCart.removeCartSelector,
			"click",
			function(){
				if(!EcomCart.ConfirmDeleteText || confirm(EcomCart.ConfirmDeleteText)) {
					var url = jQuery(this).attr("href");
					var el = jQuery(this).parents(EcomCart.orderItemHolderSelector);
					jQuery(el).slideUp(
						"slow",
						function() {jQuery(el).remove();}
					);
					EcomCart.getChanges(url, null, this);
				}
				return false;
			}
		);
	},

	/**
	 * add ajax functionality to "remove from cart" links
	 * @param String withinSelector: area where these links can be found, the more specific the better (faster)
	 */
	addRemoveLinks: function (withinSelector) {
		jQuery(withinSelector).delegate(
			EcomCart.removeLinkSelector,
			"click",
			function(){
				if(EcomCart.unconfirmedDelete || confirm(EcomCart.confirmDeleteText)) {
					var url = jQuery(this).attr("href");
					EcomCart.getChanges(url, null, this);
					return false;
				}
				return false;
			}
		);
	},

	//#################################
	// UPDATE PAGE
	//#################################

	/**
	 * get JSON data from server
	 * @param String url: URL for getting data (ajax request)
	 * @param Array params: parameters to add to ajax request
	 * @param Object loadingElement: the element that is being clicked or should be shown as "loading"
	 */
	getChanges: function(url, params, loadingElement) {
		if(params === null) {
			params = {};
		}
		if(EcomCart.ajaxButtonsOn) {
			params.ajaxButtonsOn = true;
		}
		if(EcomCart.openAjaxCalls > 1) {
			params.manyrequests = 1;
		}
		var loadingIndex = this.addLoadingSelector(loadingElement)
		params.loadingindex = loadingIndex;
		if(EcomCart.onBeforeUpdate) {
			if(typeof EcomCart.onBeforeUpdate == 'function'){
				EcomCart.onBeforeUpdate.call(url, params, EcomCart.setChanges);
			}
		}
		EcomCart.openAjaxCalls++;
		jQuery.getJSON(url, params, EcomCart.setChanges);
	},

	/**
	 * when, for example, you click on an "add to cart" button
	 * this method adds the loading class to the clicked button
	 * and retains the element so that the loading class can be removed
	 * when the data is returned.
	 * @param element (e.g. jQuery("#MyClickableButton") )
	 * @return integer
	 */
	addLoadingSelector: function(loadingElement) {
		loadingElement = jQuery(loadingElement).parent().parent();
		jQuery(loadingElement).addClass(EcomCart.classToShowLoading);
		jQuery("body").addClass(EcomCart.classToShowPageIsUpdating);
		EcomCart.loadingSelectors[EcomCart.loadingSelectors.length] = loadingElement;
		return EcomCart.loadingSelectors.length-1;
	},


	/**
	 * apply changes to the page using the JSON data from the server.
	 * @param JSON OBJECT changes: a JSON object of changes
	 * @param String status: status of updates
	 */
	setChanges: function (changes, status) {
		EcomCart.openAjaxCalls--;
		//change to switch
		//add loadingElement to data return
		//clean up documentation at the top of the document
		if(EcomCart.debug) {console.debug("------------- SET CHANGES -----------");}
		if(changes.reload) {
			window.location = window.location;
			return;
		}
		if(EcomCart.openAjaxCalls <= 0) {
			for(var i in changes) {
				var change = changes[i];
				if(typeof(change.t) != 'undefined' && typeof(change.t) != 'undefined') {
					var type = change.t;
					var selector = change.s;
					var parameter = change.p;
					var value = EcomCart.escapeHTML(change.v);
					//class OR id
					if(EcomCart.debug) {console.debug("type" + type +", selector: " + selector +", parameter:"+ parameter +", value");}
					if(type == "class" || type == "id") {
						var additionalSelectors = "";
						if(typeof EcomCart.synonyms[selector] != 'undefined') {
							selector += ", "+EcomCart.synonyms[selector];
						}
						if(type == "id") {
							selector = '#' + selector + additionalSelectors;
						}
						else {
							var selector = '.' + selector + additionalSelectors;
						}
						//hide or show row...
						if(parameter == "hide") {
							if(value) {
								jQuery(selector).hide().addClass("hideForNow");
							}
							else {
								jQuery(selector).show().removeClass("hideForNow");
							}
						}
						//general message
						//to do: add message type as class
						else if(parameter == "message") {
							jQuery(selector).html(value);
						}
						//inner html
						else if(parameter == 'innerHTML'){
							jQuery(selector).html(value);
						}
						//attribute
						else{
							jQuery(selector).attr(parameter, value);
						}
					}
					//name: used for form fields...
					else if(type == "name") {
						jQuery('[name=' + selector + ']').each(
							function() {
								jQuery(this).attr(parameter, value);
							}
						);
					}
					//replace dropdown values
					else if(type == "dropdown") {
						var selector = '#' + selector+" select";
						if(jQuery(selector).length > 0){
							if(value.length > 0) {
								jQuery(selector).html("");
								for(var i = 0; i < value.length; i++) {
									if(parameter == value[i].id) {
										var selected = " selected=\"selected\" ";
									}
									else {
										var selected = "";
									}
									jQuery(selector).append("<option value=\""+value[i].id+"\""+selected+">"+value[i].name+"</option>");
								}
							}
						}
					}
					//add new modifier row
					else if(type == "newmodifierrow") {
						//to do: to complete
					}
					//add new item row
					else if(type == "newitemrow") {
						//to do: to complete
					}
					// replace one clas with another - e.g. inCart vs notInCart
					else if(type == "replaceclass") {
						//parameter: the items that should be examined
						//selector: items that need to get a new class
						//value: the new class that the selector items are assigned
						//class for items that are not inlist
						var without = change.without;
						//we go through all the ones that are marked as 'inCart' already
						//as part of this we check if they are still incart
						//and as part of this process, we add the "inCart" where needed
						//console.debug("starting process");
						for(var i= 0; i < selector.length;i++){
							var id = "#"+selector[i];
							jQuery(id).removeClass(without).addClass(value);
							//console.debug("adding "+id);
						}
						jQuery(parameter).each(
							function(i, el) {
								var id = jQuery(el).attr("id");
								//console.debug("checking "+id);
								var inCart = false;
								for(var i = 0; i < selector.length;i++) {
									//console.debug("testing: '"+selector[i]+"' AGAINST '"+id+"'");
									if(id == selector[i]) {
										inCart = true;
									}
									//to do - what is the javascript method for 'unset'
									//unset(selector[i]);
								}
								if(!inCart) {
									jQuery("#"+id).removeClass(value).addClass(without);
									//console.debug("removing "+id);
								}
								else {
									//console.debug("leaving "+id);
								}
							}
						)
					}
					//remove loading class from selected loading element
				}
			}
			if(EcomCart.onAfterUpdate) {
				if(typeof EcomCart.onAfterUpdate == 'function'){
					EcomCart.onAfterUpdate.call(changes, status);
				}
			}
			EcomCart.reinit();
			jQuery("body").removeClass(EcomCart.classToShowPageIsUpdating);
			for(var i = 0; i < EcomCart.loadingSelectors.length; i++) {
				jQuery(EcomCart.loadingSelectors[i]).removeClass(EcomCart.classToShowLoading);
			}
		}
	},

	/**
	 * changes to the cart based on zero OR one or more rows
	 */
	updateForZeroVSOneOrMoreRows: function() {
		if(EcomCart.cartHasItems()) {
			jQuery(EcomCart.selectorShowOnZeroItems).hide();
			jQuery(EcomCart.selectorHideOnZeroItems).each(
				function(i, el) {
					if(!jQuery(el).hasClass("hideForNow")) {
						jQuery(el).show();
					}
				}
			);
		}
		else {
			jQuery(EcomCart.selectorShowOnZeroItems).show();
			jQuery(EcomCart.selectorHideOnZeroItems).hide();
		}
	},


	//##########################################
	// HELPER FUNCTIONS
	//##########################################


	/**
	 * cleaning up strings
	 * @param String str
	 * @return string
	 */
	escapeHTML: function (str) {
		return str;
	},


	/**
	 * check if there are any items in the cart
	 * @return Boolean
	 */
	cartHasItems: function() {
		return jQuery(EcomCart.selectorItemRows).length > 0 ? true : false;
	},

	/**
	 * check if a particular variable is set
	 * @param Mixed
	 * @return Boolean
	 */
	variableIsSet: function(variable) {
		if(typeof(variable) == 'undefined' || typeof variable == 'undefined' || variable == 'undefined') {
			return false;
		}
		return true;
	},


	/**
	 * check if a particular variable is set AND has a value
	 * @param Mixed
	 * @return Boolean
	 */
	variableSetWithValue: function(variable) {
		if(EcomCart.variableIsSet(variable)) {
			if(variable) {
				return true;
			}
		}
		return false;
	},



	//#################################
	// Simple Dialogue
	//#################################

	/**
	 * Setup dialogue links
	 */
	initColorboxDialog: function(){
		jQuery(EcomCart.colorboxDialogSelector).colorbox(
			EcomCart.colorboxDialogOptions
		);
	}




}






