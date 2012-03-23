<span>
	$Prefix $FirstName $Surname,
	<% if Address %>$Address, <% end_if %>
	<% if Address2 %>$Address2, <% end_if %>
	<% if City %>$City, <% end_if %>
	<% if State %>$State, <% end_if %>
	<% if PostalCode %>$PostalCode, <% end_if %>
	<% if FullCountryName %>$FullCountryName, <% end_if %>
	<% if Phone %>$Phone, <% end_if %>
	<% if MobilePhone %>MobilePhone, <% end_if %>
	<% if Email %>$Email, <% end_if %>
	<br /><a href="$RemoveLink" class="noLongerInUse" rel="$ID"><% _t("NOLONGERINUSE", "no longer in use") %>.</a>
</span>
