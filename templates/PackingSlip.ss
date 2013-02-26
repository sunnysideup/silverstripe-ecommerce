<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title><% _t("Order.PACKING_SLIP", "Packing Slip") %> $Order.Title</title>
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
				<h3><% _t("Order.DELIVER_TO", "Deliver to:") %></h3>
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


