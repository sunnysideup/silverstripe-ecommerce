<address>
	<span class="nameSpan">$Prefix $FirstName $Surname</span>
	<% if Email %><span class="emailSpan">$Email</span><% end_if %>
	<% if Phone %><span class="phoneSpan">$Phone</span><% end_if %>
	<% if Address %><span class="addressSpan">$Address</span><% end_if %>
	<% if Address2 %><span class="address2Span">$Address2</span><% end_if %>
	<% if City %><span class="citySpan">$City</span><% end_if %>
	<% if PostalCode %><span class="postalCodeSpan">$PostalCode</span><% end_if %>
	<% if RegionCode %><span class="regionCodeSpan">$RegionCode</span><% end_if %>
	<% if Region %><span class="regionCodeSpan">$Region.Name</span><% end_if %>
	<% if FullCountryName %><span class="countrySpan">$FullCountryName</span><% end_if %>
</address>
<a href="$RemoveLink" class="noLongerInUse" rel="$ID"><% _t("Order.REMOVETHISADDRESS", "address no longer in use.") %></a>
<div class="clearer"></div>
