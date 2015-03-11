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
		<% end_if %>
	</p>
<% end_if %>
