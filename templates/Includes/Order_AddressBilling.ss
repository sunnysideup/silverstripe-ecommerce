<% if BillingAddressID %>
	<% control BillingAddress %>
<address class="addressSection" id="BillingAddressSection">
	<% include Order_AddressBillingInner %>
</address>
	<% end_control %>
<% else %>
<p>No billing address available.</p>
<% end_if %>
