<% if BillingAddressID %>
	<% with BillingAddress %>
<address class="addressSection" id="BillingAddressSection">
	<% include Order_AddressBillingInner %>
</address>
	<% end_with %>
<% else %>
<p>No billing address available.</p>
<% end_if %>
