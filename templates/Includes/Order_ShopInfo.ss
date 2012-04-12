<% if Order %><% control Order %><% control SiteConfig %>
<div id="ShopInfo">
	<% if EmailLogo %><img src="$EmailLogo.getAbsoluteURL" alt="Logo - $EmailLogo.Title" /><% end_if %>
	<h1 class="title"><% _t("Order.ORDERCONFIRMATION", "Order Confirmation") %> $Title</h1>
	<% if ShopPhysicalAddress %><div id="ShopPhysicalAddress">$ShopPhysicalAddress</div><% end_if %>
</div>
<% end_control %><% end_control %><% end_if %>
