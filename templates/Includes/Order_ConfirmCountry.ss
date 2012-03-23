<% if FixedCountry %>
	<p class="confirmCountry">
		Sales are restricted to $FixedCountry.
	</p>
<% else %>
	<% if ExpectedCountryName %>
	<p class="confirmCountry">
		This information is based on a sale to <span class="$ExpectedCountryClassName">$ExpectedCountryName</span>.
		Please <a href="#{$CountryFieldID}" class="changeCountryLink">change your country</a> if this is incorrect.
	</p>
	<div id="ChangeCountryHolder"></div>
	<% end_if %>
<% end_if %>
