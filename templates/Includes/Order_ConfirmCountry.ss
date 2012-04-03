<% if FixedCountry %>
	<p class="confirmCountry">
		<% _t("Order.SALESRESTRICTEDTO","Sales are restricted to") %>
		$FixedCountry.
	</p>
<% else %>
	<p class="confirmCountry">
		<a href="#{$AJAXDefinitions.CountryFieldID}" class="changeCountryLink"><% _t("Order.CHECKYOURCOUNTRY","Please check your country") %></a>
		:
		<span class="$AJAXDefinitions.ExpectedCountryClassName">$ExpectedCountryName</span>
	</p>
	<div id="ChangeCountryHolder"></div>
<% end_if %>
