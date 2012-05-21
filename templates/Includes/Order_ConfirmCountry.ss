<% if CanShowStep(orderformaddress) %>
<% if FixedCountry %>
	<p class="confirmCountry">
		<% _t("Order.SALESRESTRICTEDTO","Sales are restricted to:") %>
		$FixedCountry.
	</p>
<% else %>
	<p class="confirmCountry">
		<% if ExpectedCountryName %>
		<% _t("Order.BASEDONASALETOCOUNTRYX","Information below is based on a sale to: ") %>
		<span class="$AJAXDefinitions.ExpectedCountryClassName">$ExpectedCountryName</span>
		<% _t("Order.CHANGECOUNTRY","If this is incorrect then please proceed and update your details.") %>
		<% end_if %>
	</p>
	<div id="ChangeCountryHolder"></div>
<% end_if %>
<% end_if %>
