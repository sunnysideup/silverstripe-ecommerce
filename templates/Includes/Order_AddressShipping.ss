<% if UseShippingAddress %>
	<% if ShippingAddress %>
		<% control ShippingAddress %>
<address class="addressSection" id="ShippingAddressSection">
	<% include Order_AddressShippingInner %>
</address>
		<% end_control %>
	<% else  %>
		<p>
			<% _t("NOSHIPPINGADDRESSAVAILABLE", "No shipping address available.") %>
		</p>
	<% end_if %>
<% else %>
	<% if BillingAddressID %>
		<% control BillingAddress %>
<address class="addressSection" cellspacing="0" cellpadding="0" id="ShippingAddressSection">
<% include Order_AddressBillingInner %>
</address>
		<% end_control %>
	<% end_if %>
<% end_if %>
