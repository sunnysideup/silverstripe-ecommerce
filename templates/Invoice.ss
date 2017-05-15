<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
	<% base_tag %>
		$MetaTags
		<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
	<!-- todo: allow printing multiple invoices at once -->
	<div style="page-break-after: always;">
		<% include Order_ShopInfo_Invoice %>
		<% with Order %>
			<% include Order %>
            <% include Order_FeedbackFormLink %>
		<% end_with %>
	</div>
	<script type="text/javascript">if (window ==window.top) {window.setTimeout(function(){window.print();}, 1000);}</script>
</body>
</html>
