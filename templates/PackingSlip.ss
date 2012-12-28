<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
	<% base_tag %>
		$MetaTags
		<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
	<div style="page-break-after: always;" id="Wrapper">
		<h2><% _t("Order.PACKINGSLIP", "Packing Slip") %></h2>
		<div id="AddressesHolder">
			<div id="Sender" class="section">
				<h3><% _t("Order.SENDER", "Sender:") %></h3>
				<% include Order_ShopInfo %>
			</div>
		<% with Order %>
			<div id="Recipient" class="section">
				<h3><% _t("Order.DELIVERTO", "Deliver to:") %></h3>
				<% include Order_AddressShipping %>
			</div>
			<div class="clear"></div>
		</div>
		<div id="ItemsHolder" class="section">
			<h3><% _t("Order.ITEMS", "Items:") %></h3>
			<% include Order_Content_Items_Only_No_Prices %>
		</div>
		<% end_with %>
	</div>
	<script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>
</body>
</html>


