<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
	<% base_tag %>
	<title><% _t("Order.PRINTORDERS","Print Orders") %></title>
</head>
<body>
	<!-- todo: allow printing multiple invoices at once -->
	<div style="page-break-after: always;">
		<h1 class="title">$SiteConfig.Title Invoice</h1>
		<p id="ShopPhysicalAddress">$SiteConfig.ShopPhysicalAddress</p>
		<% control Order %>
			<% include Order %>
		<% end_control %>
	</div>
<script type="text/javascript">window.print();</script>
</body>
</html>


