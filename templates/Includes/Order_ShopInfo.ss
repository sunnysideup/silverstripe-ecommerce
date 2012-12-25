<% if Order %><% with Order %><% with EcomConfig %>
<div id="ShopInfo">
	<% if EmailLogo %><img src="$EmailLogo.getAbsoluteURL" alt="Logo - $EmailLogo.Title" /><% end_if %>
	<h1 class="title">$InvoiceTitle</h1>
	<% if ShopPhysicalAddress %><div id="ShopPhysicalAddress">$ShopPhysicalAddress</div><% end_if %>
</div>
<% end_with %><% end_with %><% end_if %>
