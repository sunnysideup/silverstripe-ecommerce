<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
	<% base_tag %>
		$MetaTags
		<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
	<div style="page-break-after: always;">
		<% include Order_ShopInfo %>
		<% control Order %>
			<% include Order_AddressShipping %>
		<% end_control %>
	</div>
	<script type="text/javascript">window.setTimeout(function(){window.print();}, 1000)</script>
</body>
</html>


