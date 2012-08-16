<address>
	$ShippingPrefix $ShippingFirstName $ShippingSurname
	<% if ShippingAddress %><span class="addressSpan">$ShippingAddress</span><% end_if %>
	<% if ShippingAddress2 %><span class="address2Span">$ShippingAddress2 </span><% end_if %>
	<% if ShippingCity %><span class="citySpan">$ShippingCity </span><% end_if %>
	<% if ShippingState %><span class="stateSpan">$ShippingState </span><% end_if %>
	<% if ShippingPostalCode %><span class="postalCodeSpan">$ShippingPostalCode </span><% end_if %>
	<% if ShippingFullCountryName %><span class="countrySpan">$ShippingFullCountryName </span><% end_if %>
	<% if ShippingMobilePhone %><span class="mobileSpan">$ShippingMobilePhone </span><% end_if %>
	<% if ShippingPhone %><span class="shippingPhoneSpan">$ShippingPhone </span><% end_if %>
</address>
<a href="$RemoveLink" class="noLongerInUse" rel="$ID"><% _t("Order.REMOVETHISADDRESS", "address no longer in use.") %></a>
<div class="clearer"></div>
