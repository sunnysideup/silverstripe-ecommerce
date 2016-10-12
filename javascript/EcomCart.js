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


var EcomCart = {

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
     * @var Int
     */
    openAjaxCalls: 0,

    /**
     * are there any items in the cart
     * @var Boolean
     */
    cartHasItems: false,

    /**
     * This is the data that we start with (which may be contained in the original HTML)
     * @var Array
     */
    initialData: [],
        set_initialData: function(a) {this.initialData = a;},

    /**
     *  array of callbacks to call after update
     *
     * @type Array
     */
    reinitCallbacks: [],

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
     * selector of the dom elements shown when there are no items in cart.
     */
    selectorShowOnZeroItems: ".showOnZeroItems",
        set_selectorShowOnZeroItems: function(s) {this.selectorShowOnZeroItems = s;},

    /**
     * selector of the dom elements that is hidden on zero items.
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
     * turn on / off the ajax buttons outside of the cart
     * (e.g. add this product to cart, delete from cart)
     * @var Boolean
     */
    ajaxButtonsOn: true,
        set_ajaxButtonsOn: function(b) {this.ajaxButtonsOn = b;},

    /**
     * Can the Product List be updated using AJAX?
     *
     * @var Boolean
     */
    ajaxifyProductList: false,
        set_ajaxifyProductList: function(b) {this.ajaxifyProductList = b;},

    /**
     * Is the product list from a cached source?
     *
     * This is important to know, because in this case
     * we have to disable the SecurityID by adding
     * cached=1 to all URLs
     *
     * @var Boolean
     */
    productListIsFromCachedSource: true,

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

    /**
     * the selector used to identify the cart related menu items (e.g. cart / checkout)
     */
    cartMenuLinksSelector: ".cartlink",
        set_cartMenuLinksSelector: function(s) {this.cartMenuLinksSelector = s;},



    //#################################
    // AJAX PRODUCT LINKS
    //#################################

    /**
     * the selector used to identify links
     * that change the product list. These can be ajaxified so that the list
     * is using AJAX rather than reload the whole page.
     * @var String
     */
    ajaxifiedListAdjusterSelectors: ".ajaxifyMyProductGroupLinks",
        set_ajaxifiedListAdjusterSelectors: function(s) {this.ajaxifiedListAdjusterSelectors = s;},


    /**
     * selector of element that will be replaced by the new
     * product list
     * @var String
     */
    ajaxifiedListHolderSelector: "#ProductGroup",
        set_ajaxifiedListHolderSelector: function(s) {this.ajaxifiedListsSelector = s;},



    /**
     * Hidden page title, used when products are updated using the
     * @var String
     */
    hiddenPageTitleID: "#HiddenPageTitleID",
        set_hiddenPageTitleID: function(s) {this.hiddenPageTitleID = s;},



    /**
     * Hidden page title, used when products are updated using the
     * @var function
     */
    ajaxifiedProductsCallBack: function(){},
        set_ajaxifiedProductsCallBack: function(f) {this.ajaxifiedProductsCallBack = f;},



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
        height: "95%",
        width: "95%",
        maxHeight: "95%",
        maxWidth: "95%",
        loadingClass: "loading",
        iframe: true,
        onOpen: function (event) {
            EcomCart.reinit(true);
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
        if(typeof EcomCartOptions !== "undefined") {
            for (var key in EcomCartOptions){
                if (EcomCartOptions.hasOwnProperty(key)) {
                     this[key] = EcomCartOptions[key];
                }
            }
        }
        //make sure that country and region changes are applied to Shopping Cart
        EcomCart.countryAndRegionUpdates();
        //setup an area where the user can change their country / region
        EcomCart.changeCountryFieldSwap();
        //ajaxify product list
        EcomCart.addAjaxificationOfProductList();
        //cart buttons
        if(EcomCart.ajaxButtonsOn) {
            //make sure that "add to cart" links are updated with AJAX
            EcomCart.addAddLinks(EcomCart.ajaxLinksAreaSelector);
            //make sure that "remove from cart" links are updated with AJAX
            EcomCart.addRemoveLinks(EcomCart.ajaxLinksAreaSelector);
            //make sure that "delete from cart" links are updated with AJAX - looking at the actual cart itself.
            EcomCart.addCartRemove(EcomCart.ajaxLinksAreaSelector);
        }
        //EcomCart.updateForZeroVSOneOrMoreRows(); is only required after changes are made
        //because HTML loads the right stuff by default.
        //EcomCart.updateForZeroVSOneOrMoreRows();
        EcomCart.initColorboxDialog();
        EcomCart.setChanges(EcomCart.initialData, "");
        //allow ajax product list back and forth:
        window.onpopstate = function(e){
            if(e.state){
                jQuery(EcomCart.ajaxifiedListHolderSelector).html(e.state.html);
                document.title = e.state.pageTitle;
            }
        };
    },

    /**
     * runs everytime the cart is updated
     * @param Boolean changes applied? have changes been applied in the meantime.
     */
    reinit: function(changesApplied){
        //hide or show "zero items" information
        if(changesApplied) {
            EcomCart.updateForZeroVSOneOrMoreRows();
        }
        for(var i = 0; i < EcomCart.reinitCallbacks.length; i++) {
            EcomCart.reinitCallbacks[i]();
        }

    },



    //#################################
    // COUNTRY AND REGION CHANGES
    //#################################

    /**
     * sets the functions for updating country and region
     */
    countryAndRegionUpdates: function() {
        jQuery(EcomCart.countryAndRegionRootSelector).on(
            "change",
            EcomCart.ajaxCountryFieldSelector,
            function() {
                var url = EcomCart.createUrl("setcountry", this.value);
                EcomCart.getChanges(url, null, this);
            }
        );
        jQuery(EcomCart.countryAndRegionRootSelector).on(
            "change",
            EcomCart.ajaxRegionFieldSelector,
            function() {
                var url = EcomCart.createUrl("setregion", this.value);
                EcomCart.getChanges(url, null, this);
            }
        );
    },


    /**
     * gets the options from the main country field and presents them as options for the user
     * to select a new country.
     */
    changeCountryFieldSwap: function() {
        jQuery(EcomCart.countryAndRegionRootSelector).on(
            "change",
            EcomCart.selectorChangeCountryFieldHolder + " select",
            function() {
                var val = jQuery(EcomCart.selectorChangeCountryFieldHolder + " select").val();
                jQuery(EcomCart.ajaxCountryFieldSelector).val(val);
                var url = EcomCart.createUrl("setcountry",val);
                EcomCart.getChanges(url, null, this);
                jQuery(EcomCart.selectorChangeCountryLink).click();
            }
        );
    },

    /**
     * ajaxify the product list
     *
     */
    addAjaxificationOfProductList: function() {
        if(EcomCart.ajaxifyProductList) {
            jQuery(EcomCart.ajaxifiedListHolderSelector).on(
                "click",
                EcomCart.ajaxifiedListAdjusterSelectors + " a",
                function(event){
                    event.preventDefault();
                    var url = jQuery(this).attr("href");
                    jQuery.ajax(
                        {
                            beforeSend: function(){jQuery(EcomCart.ajaxifiedListHolderSelector).addClass(EcomCart.classToShowLoading);},
                            //cache: false,
                            complete: function(){jQuery(EcomCart.ajaxifiedListHolderSelector).removeClass(EcomCart.classToShowLoading);},
                            dataType: "html",
                            error: function(jqXHR, textStatus, errorThrown){
                                alert("An error occurred (" + textStatus + " " + errorThrown + ")! I will try reloading the page now.");
                                window.location.href = url;
                            },
                            success: function(data, textStatus, jqXHR){
                                jQuery(EcomCart.ajaxifiedListHolderSelector).html(data);

                                //create history
                                var pageTitle = jQuery(EcomCart.hiddenPageTitleID).text();
                                window.history.pushState(
                                    {"pageTitle":pageTitle},
                                    pageTitle,
                                    url
                                );
                                document.title = pageTitle;
                                //update changes
                                //set changes also does the reinit
                                EcomCart.openAjaxCalls++;
                                EcomCart.setChanges(EcomCart.initialData, "");
                                if (typeof(EcomProducts)  != 'undefined') {
                                    EcomProducts.reinit();
                                }
                                if(typeof(EcomCart.ajaxifiedProductsCallBack) == "function") {
                                    EcomCart.ajaxifiedProductsCallBack();
                                }
                                //scroll to the top of the product list.
                                jQuery('html, body').animate({scrollTop: jQuery(EcomCart.ajaxifiedListHolderSelector).offset().top}, 500);
                            },
                            url: url,
                        }
                    )
                }
            )
        }
    },



    //#################################
    // SETUP PAGE
    //#################################

    /**
     * adds the "add to cart" ajax functionality to links.
     * @param String withinSelector: area where these links can be found, the more specific the better (faster)
     */
    addAddLinks: function(withinSelector) {
        jQuery(withinSelector).on(
            "click",
            EcomCart.addLinkSelector,
            function(){
                var url = jQuery(this).attr("href");
                if(EcomCart.productListIsFromCachedSource) {
                    url +="&cached=1";
                }
                EcomCart.getChanges(url, null, this);
                return false;
            }
        );
    },

    /**
     * add ajax functionality to "remove from cart" links
     * outside the cart
     * @param String withinSelector: area where these links can be found, the more specific the better (faster)
     */
    addRemoveLinks: function (withinSelector) {
        jQuery(withinSelector).on(
            "click",
            EcomCart.removeLinkSelector,
            function(){
                if(EcomCart.unconfirmedDelete || confirm(EcomCart.confirmDeleteText)) {
                    var url = jQuery(this).attr("href");
                    if(EcomCart.productListIsFromCachedSource) {
                        url +="&cached=1";
                    }
                    EcomCart.getChanges(url, null, this);
                }
                return false;
            }
        );
    },


    /**
     * adds the "remove from cart" ajax functionality to links
     * IN THE CART!
     * @param String withinSelector: area where these links can be found, the more specific the better (faster)
     */
    addCartRemove: function (withinSelector) {
        jQuery(withinSelector).on(
            "click",
            EcomCart.removeCartSelector,
            function(event){
                if(!EcomCart.confirmDeleteText || confirm(EcomCart.confirmDeleteText)) {
                    var url = jQuery(this).attr("href");
                    var el = jQuery(this).parents(EcomCart.orderItemHolderSelector);
                    jQuery(el).slideUp(
                        "slow",
                        function() {
                            jQuery(el).remove();
                        }
                    );
                    EcomCart.getChanges(url, null, this);
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
            if(typeof(EcomCart.onBeforeUpdate) == 'function'){
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
     *
     *
     *
     * @return String
     */
    createUrl: function(method, variable) {
        var url = jQuery('base').attr('href') + EcomCart.shoppingCartURLSegment + "/";
        if(method) {
            url += method + "/";
        }
        if(variable) {
            url += variable + "/";
        }
        return url;
    },

    /**
     * apply changes to the page using the JSON data from the server.
     * @param JSON OBJECT changes: a JSON object of changes
     * @param String status: status of updates
     */
    setChanges: function (changes, status) {
        EcomCart.set_initialData(changes);
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
                if(typeof(change.t) != 'undefined') {
                    var type = change.t;
                    var selector = change.s;
                    var parameter = change.p;
                    var value = EcomCart.escapeHTML(change.v);
                    //class OR id
                    if(EcomCart.debug) {console.debug("type" + type +", selector: " + selector +", parameter:"+ parameter +", value");}
                    if(type == "class" || type == "id") {
                        var additionalSelectors = "";
                        if(typeof(EcomCart.synonyms[selector]) != 'undefined') {
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
                            jQuery(selector).each(
                                function(i, el) {
                                    jQuery(el).html(value);
                                }
                            );
                        }
                        //attribute
                        else{
                            jQuery(selector).attr(parameter, value);
                        }
                        if(selector == ".number_of_items") {
                            if(EcomCart.debug) {console.debug("doing .number_of_items");}
                            var numericValue = parseFloat(value);
                            if(EcomCart.debug) {console.debug("value "+numericValue);}
                            EcomCart.cartHasItems = (numericValue > 0 ? true : false);
                            //update cart menu items
                            jQuery("a"+EcomCart.cartMenuLinksSelector+",  li"+EcomCart.cartMenuLinksSelector+" > a").each(
                                function(i, el) {
                                    var myElement = el;
                                    if( ! jQuery(el).is("a")) {
                                        myElement = jQuery(el).find("a");
                                    }
                                    var innerText = jQuery(myElement).html();
                                    var numbersOnlyRE = new RegExp('(\\d+)', "g");
                                    var newInnerText = innerText.replace(numbersOnlyRE, value);
                                    jQuery(myElement).html(newInnerText);
                                }
                            );
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
                        if(EcomCart.debug) {console.debug("starting replaceclass process");}
                        jQuery("."+parameter).each(
                            function(i, el) {
                                var id = jQuery(el).attr("id");
                                if(EcomCart.debug) {console.debug("checking "+id);}
                                var inCart = false;
                                for(var i = 0; i < selector.length;i++) {
                                    if(EcomCart.debug) {console.debug("testing: '"+selector[i]+"' AGAINST '"+id+"'");}
                                    if(id == selector[i]) {
                                        inCart = true;
                                        //DO NOT REMOVE IT SO THAT WE CAN USE IT IN THE FUTURE
                                        //selector.splice(i, 1);
                                    }
                                }
                                if(inCart) {
                                    jQuery(el).removeClass(without).addClass(value);
                                }
                                else {
                                    jQuery(el).removeClass(value).addClass(without);
                                }
                            }
                        )
                    }
                    //remove loading class from selected loading element
                }
            }
            if(EcomCart.onAfterUpdate) {
                if(typeof(EcomCart.onAfterUpdate) == 'function'){
                    EcomCart.onAfterUpdate.call(changes, status);
                }
            }

            EcomCart.reinit(changes.length > 0);
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
        if(EcomCart.cartHasItems) {
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
     * check if a particular variable is set
     * @param Mixed
     * @return Boolean
     */
    variableIsSet: function(variable) {
        if(typeof(variable) == 'undefined' || variable == 'undefined') {
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
        jQuery(document).on(
            "click",
            EcomCart.colorboxDialogSelector,
            function (e) {
                EcomCart.colorboxDialogOptions.href = jQuery(this).attr('href');
                EcomCart.colorboxDialogOptions.open = true;
                jQuery.colorbox(EcomCart.colorboxDialogOptions);
                return false;
            }
        );
    }




};
