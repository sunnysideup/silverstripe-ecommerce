<address>
    <span class="shippingNameSpan">$ShippingPrefix $ShippingFirstName $ShippingSurname</span>
    <% if ShippingPhone %><span class="shippingPhoneSpan">$ShippingPhone </span><% end_if %>
    <% if ShippingCompanyName %><span class="addressSpan">$ShippingCompanyName</span><% end_if %>
    <% if ShippingAddress %><span class="addressSpan">$ShippingAddress</span><% end_if %>
    <% if ShippingAddress2 %><span class="address2Span">$ShippingAddress2 </span><% end_if %>
    <% if ShippingCity %><span class="citySpan">$ShippingCity </span><% end_if %>
    <% if ShippingPostalCode %><span class="postalCodeSpan">$ShippingPostalCode </span><% end_if %>
    <% if ShippingRegionCode %><span class="regionCodeSpan">$ShippingRegionCode </span><% end_if %>
    <% if ShippingRegion %><span class="stateSpan">$ShippingRegion.Name </span><% end_if %>
    <% if ShippingFullCountryName %><span class="countrySpan">$ShippingFullCountryName </span><% end_if %>
</address>
<form class="button-form enable-after-adding-security-id" action="$RemoveLink" method="post"<% if not $AddSecurityIdToLinks %> disabled="disabled"<% end_if %> rel="$ID">
    <input type="hidden" name="SecurityID" value="<% if $AddSecurityIdToLinks %>$SecurityIDToken<% else %><% end_if %>"/>
    <button
        type="submit"
        class="button"
        title="Remove address"
    >
        <% _t("Order.REMOVETHISADDRESS", "address no longer in use.") %>
    </button>
</form>
<div class="clearer"></div>
