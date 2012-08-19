<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
	<% base_tag %>
		$MetaTags
		<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
	<div style="page-break-after: always;">
		<h2><% _t("Order.PACKINGSLIP", "Packing Slip") %></h2>
		<hr />
		<div id="Sender">
			<h3><% _t("Order.SENDER", "sender:") %></h3>
			<% include Order_ShopInfo %>
			<hr />
		</div>
		<% control Order %>
			<div id="ItemsHolder">
				<h3><% _t("Order.ITEMS", "Items:") %></h3>
				<% include Order_Content_Items_Only_No_Prices %>
				<hr />
			</div>
			<div id="Recipient">
				<h3><% _t("Order.DELIVERTO", "deliver to:") %></h3>
				<% include Order_AddressShipping %>
				<hr />
			</div>
		<% end_control %>
	</div>
	<script type="text/javascript">window.setTimeout(function(){window.print();}, 1000)</script>
</body>
</html>


