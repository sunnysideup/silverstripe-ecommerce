<address>
	$Prefix $FirstName $Surname
	<% if Address %><span class="addressSpan">$Address</span><% end_if %>
	<% if Address2 %><span class="address2Span">$Address2</span><% end_if %>
	<% if City %><span class="citySpan">$City</span><% end_if %>
	<% if State %><span class="stateSpan">$State</span><% end_if %>
	<% if PostalCode %><span class="postalCodeSpan">$PostalCode</span><% end_if %>
	<% if FullCountryName %><span class="countrySpan">$FullCountryName</span><% end_if %>
	<% if MobilePhone %><span class="mobileSpan">$MobilePhone</span><% end_if %>
	<% if Phone %><span class="phoneSpan">$Phone</span><% end_if %>
	<% if Email %><span class="emailSpan">$Email</span><% end_if %>
</address>
<a href="$RemoveLink" class="noLongerInUse" rel="$ID"><% _t("Order.REMOVETHISADDRESS", "address no longer in use.") %></a>
<div class="clearer"></div>
