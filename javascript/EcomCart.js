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
  * USING tag ID / class name
  *  - update html
  *  - hide / show
  *  - update attribute
  * WITH name:
  *  - update attribute (e.g. update value for quantity field using the field's name)
  * WITH dropdownArray
  *  - update dropdown
  * WITH rows:
  *  - add / delete TO BE IMPLEMENTED
  *
  *
  * The data returned is:
  * - information on each order item
  * - information on each order modifier
  * - a list of all buyables in cart
  * - an medium and small representation of the cart
  * - total items in cart
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
	 * selector to identify input field for selecting country.
	 */
	shoppingCartURLSegment: "shoppingcart",
		set_shoppingCartURLSegment: function(s) {this.shoppingCartURLSegment = s;},


	//#################################
	// COUNTRY + REGION SELECTION
	//#################################

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
	 * the selector for the link that allows the customer to change their country
	 */
	selectorChangeCountryLink: ".changeCountryLink",

	/**
	 * the selector for the dom element used for allowing the customer to change their country
	 */
	selectorChangeCountryFieldHolder: "#ChangeCountryHolder",

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
	 * element to which the loading class is added
	 */
	attachLoadingClassTo: "body",
		set_attachLoadingClassTo: function(s) {this.attachLoadingClassTo = s;},


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
	 * selector of actions hidden during update
	 */
	submitSelector: "#OrderForm_OrderForm_action_processOrder",
		set_submitSelector: function(s) {this.hideClass = s;},



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
		set_removeLinkSelector: function(s) {this.removeLinkSelector = s;},


	//#################################
	// INIT AND RESET FUNCTIONS
	//#################################

	/**
	 * initialises all the ajax functionality
	 */
	init: function () {
		//hide or show "zero items" information
		EcomCart.updateForZeroVSOneOrMoreRows();
		//make sure that country and region changes are applied to Shopping Cart
		EcomCart.countryAndRegionUpdates();
		//setup an area where the user can change their country / region
		EcomCart.changeCountryFieldSwap();
		if(EcomCart.ajaxButtonsOn) {
			//make sure that "add to cart" links are updated with AJAX
			jQuery(EcomCart.ajaxLinksAreaSelector).addAddLinks();
			//make sure that "remove from cart" links are updated with AJAX
			jQuery(EcomCart.ajaxLinksAreaSelector).addRemoveLinks();
			//make sure that "delete from cart" links are updated with AJAX - looking at the actual cart itself.
			jQuery(EcomCart.ajaxLinksAreaSelector).addCartRemove();
		}
	},


	//#################################
	// COUNTRY AND REGION CHANGES
	//#################################

	/**
	 * sets the functions for updating country and region
	 */
	countryAndRegionUpdates: function() {
		jQuery(EcomCart.ajaxCountryFieldSelector).live(
			"change",
			function() {
				var url = jQuery('base').attr('href') + EcomCart.shoppingCartURLSegment + "/setcountry/" + this.value + "/";
				EcomCart.getChanges(url, null);
			}
		);
		jQuery(EcomCart.ajaxRegionFieldSelector).live(
			"change",
			function() {
				var url = jQuery('base').attr('href')  + EcomCart.shoppingCartURLSegment + "/setregion/" + this.value + "/";
				EcomCart.getChanges(url, null);
			}
		);
	},


	/**
	 * gets the options from the main country field and presents them as options for the user
	 * to select a new country.
	 */
	changeCountryFieldSwap: function() {
		jQuery(EcomCart.selectorChangeCountryFieldHolder).hide();
		jQuery(EcomCart.selectorChangeCountryLink).click(
			function(event) {
				if(jQuery(EcomCart.selectorChangeCountryFieldHolder).is(":hidden")) {
					var options = jQuery(EcomCart.ajaxCountryFieldSelector).html();
					var html = "<select>" + options + "</select>";
					jQuery(EcomCart.selectorChangeCountryFieldHolder).html(html).slideDown();
					jQuery(EcomCart.selectorChangeCountryFieldHolder+" select").val(jQuery(EcomCart.ajaxCountryFieldSelector).val());
				}
				else {
					jQuery(EcomCart.selectorChangeCountryFieldHolder).slideUp(
						"slow",
						function() {
							jQuery(EcomCart.selectorChangeCountryFieldHolder).html("");
						}
					);
				}
				event.preventDefault();
			}
		);
		jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").live(
			"change",
			function() {
				var val = jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").val();
				jQuery(EcomCart.ajaxCountryFieldSelector).val(val);
				var url = jQuery('base').attr('href') + EcomCart.shoppingCartURLSegment + "/setcountry/" + val + "/";
				EcomCart.getChanges(url, null);
				jQuery(EcomCart.selectorChangeCountryLink).click();
			}
		);
	},



	//#################################
	// UPDATE PAGE
	//#################################

	/**
	 * get JSON data from server
	 */
	getChanges: function(url, params) {
		jQuery(EcomCart.attachLoadingClassTo).addClass(EcomCart.classToShowLoading);
		if(params === null) {
			params = {};
		}
		if(EcomCart.ajaxButtonsOn) {
			params.ajaxButtonsOn = true;
		}
		jQuery(EcomCart.submitSelector).attr("disabled", "disabled").addClass("disabled");
		jQuery.getJSON(url, params, EcomCart.setChanges);
	},


	/**
	 * apply changes to the page using the JSON data from the server.
	 */
	setChanges: function (changes) {
		for(var i in changes) {
			var change = changes[i];
			if(typeof(change.parameter) != 'undefined' && typeof(change.value) != 'undefined') {
				var parameter = change.parameter;
				var value = EcomCart.escapeHTML(change.value);
				//selector Types
				var id = change.id;
				var name = change.name;
				var className = change.className;
				var dropdownArray = change.dropdownArray;
				var newItemRow = change.newItemRow;
				var newModifierRow = change.newModifierRow;
				if(EcomCart.variableSetWithValue(id) || EcomCart.variableSetWithValue(className)) {
					if(EcomCart.variableSetWithValue(id)) {
						var mySelector = '#' + id;
					}
					else {
						var mySelector = '.' + className;
					}
					var id = '#' + id;
					//hide or show row...
					if(parameter == "hide") {
						if(change.value) {
							jQuery(mySelector).hide();
							jQuery(mySelector).addClass("hideForNow");
						}
						else {
							jQuery(mySelector).show();
							jQuery(mySelector).removeClass("hideForNow");
						}
					}

					//general message
					else if(EcomCart.variableSetWithValue(change.isOrderMessage)) {
						jQuery(mySelector).html(value);
					}
					else if(parameter == 'innerHTML'){
						jQuery(mySelector).html(value);
					}
					else{
						jQuery(mySelector).attr(parameter, value);
					}
				}

				//used for form fields...
				else if(EcomCart.variableSetWithValue(name)) {
					jQuery('[name=' + name + ']').each(
						function() {
							jQuery(this).attr(parameter, value);
						}
					);
				}

				//used for dropdowns
				else if(EcomCart.variableSetWithValue(dropdownArray)) {
					var selector = '#' + dropdownArray+" select";
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

				//used to add new item row
				else if(EcomCart.variableSetWithValue(newItemRow)) {
				}
				else if(EcomCart.variableSetWithValue(newModifierRow)) {
				}
			}
			//ADD and REMOVE ROWS.....TO BE ADDED HERE....
		}
		EcomCart.updateForZeroVSOneOrMoreRows();
		jQuery(EcomCart.attachLoadingClassTo).removeClass(EcomCart.classToShowLoading);
		jQuery(EcomCart.submitSelector).attr("disabled", "").removeClass("disabled");
	},


	/**
	 * changes to the cart based on zero OR one or more rows
	 *
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
	 * @return Boolean
	 */
	variableSetWithValue: function(variable) {
		if(EcomCart.variableIsSet(variable)) {
			if(variable) {
				return true;
			}
		}
		return false;
	}

}


jQuery.fn.extend({
		addAddLinks: function() {
			jQuery(this).find(EcomCart.addLinkSelector).live(
				"click",
				function(){
					var url = jQuery(this).attr("href");
					//to do: hide row / li
					EcomCart.getChanges(url, null);
					return false;
				}
			);
		},

		addCartRemove: function () {
			jQuery(this).find(EcomCart.removeCartSelector).live(
				"click",
				function(){
					if(!EcomCart.ConfirmDeleteText || confirm(EcomCart.ConfirmDeleteText)) {
						var url = jQuery(this).attr("href");
						jQuery(this).parents(EcomCart.orderItemHolderSelector).slideUp();
						EcomCart.getChanges(url, null);
					}
					return false;
				}
			);
		},

		/**
		 * add ajax functionality to "remove from cart" links
		 *
		 */
		addRemoveLinks: function () {
			jQuery(this).find(EcomCart.removeLinkSelector).live(
				"click",
				function(){
					if(EcomCart.unconfirmedDelete || confirm(EcomCart.confirmDeleteText)) {
						var url = jQuery(this).attr("href");
						jQuery(this).parents(EcomCart.orderItemHolderSelector).slideUp();
						EcomCart.getChanges(url, null);
						return false;
					}
					return false;
				}
			);
		}

});





