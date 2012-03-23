<span>
	$ShippingPrefix $ShippingFirstName $ShippingSurname,
	<% if ShippingAddress %>$ShippingAddress,<% end_if %>
	<% if ShippingAddress2 %>$ShippingAddress2,<% end_if %>
	<% if ShippingCity %>$ShippingCity,<% end_if %>
	<% if ShippingState %>$ShippingState,<% end_if %>
	<% if ShippingPostalCode %>$ShippingPostalCode,<% end_if %>
	<% if ShippingFullCountryName %>$ShippingFullCountryName,<% end_if %>
	<% if ShippingMobilePhone %>$ShippingMobilePhone,<% end_if %>
	<% if ShippingPhone %>$ShippingPhone,<% end_if %>
	<br /><a href="$RemoveLink" class="noLongerInUse" rel="$ID"><% _t("NOLONGERINUSE", "no longer in use") %>.</a>
</span>
