<span class="currencyQuantifier">
<% if DisplayPrice %>
	($DisplayPrice.Nice)
<% else %>
	<% if EcomConfig.Currency %>($EcomConfig.Currency)<% end_if %>
<% end_if %>
</span>



