/**
 * manages autocomplete for the Buyable Select Field.
 * Used in the CMS
 *
 * Uses the Entwine jQuery Extension
 *
 * @todocan only have one EcomBuyableSelectField per page.
 *
 *
 */

(function($) {
    $.entwine('ecommerce', function($) {
        /**
         */
        $('#FindBuyable input.text').entwine({
            onmatch : function() {
                EcomBuyableSelectField.init();
            }
        });

    });

}(jQuery));

/*
(function($){
    jQuery(document).ready(
        function() {
            EcomBuyableSelectField.init();
        }
    );
})(jQuery);
*/

EcomBuyableSelectField = {

    url_segment: "ecommercebuyabledatalist",
      set_url_segment: function(s) {this.url_segment = s;},
    /**
     * the class that is being added when we are searching...
     * @var String
     */
    loadingClass: "loading",
        set_loadingClass: function(s) {this.loadingClass = i;},


    /**
     * the term that is being searched for
     * @var String
     */
    requestTerm: "",
        set_requestTerm: function(s) {this.requestTerm = i;},


    /**
     * the term that is being searched for
     * @var String
     */
    nothingFound: "nothing items found",
        set_nothingFound: function(s) {this.nothingFound = s;},

    /**
     * the term that is being searched for
     * @var String
     */
    searching: "searching",
        set_searching: function(s) {this.searching = s;},


    /**
     * number of suggestions that are being returned
     * @var Int
     */
    countOfSuggestions: 7,
        set_countOfSuggestions: function(i) {this.countOfSuggestions = i;},


    /**
     * number of characters before we start searching
     * @var Int
     */
    minLength: 2,
        set_minLength: function(i) {this.minLength = i;},

    /**
     * delay in milliseconds before we start searching
     * @var Int
     */
    delayInMilliSeconds: 500,
        set_delayInMilliSeconds: function(i) {this.delayInMilliSeconds = i;},

    /**
     * name of the field that you enter your search criteria
     * @var String
     */
    fieldName: "",
        set_fieldName: function(s) {this.fieldName = s;},

    /**
     * name of the form
     * @var String
     */
    formName: "",
        set_formName: function(s) {this.formName = s;},

    /**
     * selector of the field that shows the buyable when the buyable has already been selected
     * @var String
     */
    selectedBuyableFieldName: "",
        set_selectedBuyableFieldName: function(s) {this.selectedBuyableFieldName = s;},

    /**
     * selector of the field that shows the buyable when the buyable has already been selected
     * @var String
     */
    selectedBuyableFieldID: "",
        set_selectedBuyableFieldID: function(s) {this.selectedBuyableFieldID = s;},

    init: function() {
        var selector = "#FindBuyable input.text";
        jQuery(document).on(
            "focus",
            selector,
            EcomBuyableSelectField.attach()
        );
    },

    attach: function(){
        EcomBuyableSelectField.fieldName += "-FindBuyable" ;
        jQuery("#FindBuyable input.text")
            .focus(
                function() {
                    var labelSelector = "label[for='" + jQuery(this).attr("id") + "']";
                    jQuery(labelSelector).addClass("hasFocus");
                }
            )
            .blur(
                function() {
                    var labelSelector = "label[for='" + jQuery(this).attr("id") + "']";
                    jQuery(labelSelector).removeClass("hasFocus");
                    if(jQuery(this).val().length == 0) {
                        jQuery(labelSelector).removeClass("hasText");
                    }
                    else {
                        jQuery(labelSelector).addClass("hasText");
                    }
                }
            )
            .keydown(
                function(event) {
                    var labelSelector = "label[for='" + jQuery(this).attr("id") + "']";
                    if(jQuery(this).val().length > 1) {
                        jQuery(labelSelector).addClass("hasText");
                    }
                    else {
                        jQuery(labelSelector).removeClass("hasText");
                    }
                }
            )
            .autocomplete(
                    {
                    delay: EcomBuyableSelectField.delayInMilliSeconds,
                    //delay before we start searching
                    delay: EcomBuyableSelectField.delayInMilliSeconds,

                    //number of characters entered before we start searching
                    minLength: EcomBuyableSelectField.minLength,

                    source: function(request, response) {
                        jQuery("label[for='"+EcomBuyableSelectField.fieldName+"']'").parent().addClass(EcomBuyableSelectField.loadingClass);
                        EcomBuyableSelectField.requestTerm = request.term;
                        jQuery.ajax(
                            {
                                type: "POST",
                                url: jQuery('base').attr('href') + EcomBuyableSelectField.url_segment+"/json/",
                                dataType: "json",
                                data: {
                                    term: request.term,
                                    countOfSuggestions: EcomBuyableSelectField.countOfSuggestions
                                },
                                error: function(xhr, textStatus, errorThrown) {
                                    alert("Error: " + xhr.responseText+errorThrown+textStatus);
                                },
                                success: function(data) {
                                    response(
                                        jQuery.map(
                                            data,
                                            function(c) {
                                                return {
                                                    label: c.Title,
                                                    value: EcomBuyableSelectField.requestTerm,
                                                    title: c.Title,
                                                    className: c.ClassName,
                                                    id: c.ID,
                                                    version: c.Version
                                                }
                                            }
                                        )
                                    );
                                    if(data.length < 1) {
                                        EcomBuyableSelectField.showCurrentSituation(EcomBuyableSelectField.nothingFound);
                                    }
                                    jQuery( "label[for='"+EcomBuyableSelectField.fieldName+"']'").parent().removeClass(EcomBuyableSelectField.loadingClass);
                                }
                            }
                        );
                    },

                    //after we finish the search (what happens when the data comes back...
                    select: function(event, ui) {
                        var formSelector = "#" + jQuery("#FindBuyable").parents("form").attr("id");
                        if(EcomBuyableSelectField.formName) {
                            formSelector = "#"+EcomBuyableSelectField.formName;
                        }
                        if(
                            jQuery(formSelector+ " input[name='BuyableID']").length == 0 ||
                            jQuery(formSelector+ " input[name='BuyableClassName']").length  == 0 ||
                            jQuery(formSelector+ " input[name='Version']").length  == 0
                        ) {
                            EcomBuyableSelectField.showCurrentSituation("Error: can not find BuyableID or BuyableClassName or Version field");
                        }
                        else {
                            jQuery(formSelector+ " input[name='BuyableID']").val(ui.item.id);
                            jQuery(formSelector+ " input[name='BuyableClassName']").val(ui.item.className);
                            jQuery(formSelector+ " input[name='Version']").val(ui.item.version);
                            EcomBuyableSelectField.showCurrentSituation(ui.item.title);
                        }
                    }
                }
            );
    },

    showCurrentSituation: function(situation) {
        jQuery("input[name=\'"+EcomBuyableSelectField.selectedBuyableFieldName+"\']").val(situation);
        jQuery("#FindBuyable span#FindBuyable-SelectedBuyable").text(situation);
    }

}
