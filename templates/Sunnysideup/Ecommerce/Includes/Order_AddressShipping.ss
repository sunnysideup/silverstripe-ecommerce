<% if UseShippingAddress %>
	<% if ShippingAddress %>
		<% with ShippingAddress %>
<address class="addressSection" id="ShippingAddressSection">
	<% include Sunnysideup\Ecommerce\Includes\Order_AddressShippingInner %>
</address>
		<% end_with %>
	<% else  %>
		<p>
			<% _t("NOSHIPPINGADDRESSAVAILABLE", "No shipping address available.") %>
		</p>
	<% end_if %>
<% else %>
	<% if BillingAddressID %>
		<% with BillingAddress %>
<address class="addressSection" id="ShippingAddressSection">
<% include Sunnysideup\Ecommerce\Includes\Order_AddressBillingInner %>
</address>
		<% end_with %>
	<% end_if %>
<% end_if %>
