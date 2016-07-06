<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<% base_tag %>
	$MetaTags
	<link rel="shortcut icon" href="/favicon.ico" />
</head>
<body>
	<% if ErrorMessage %><div id="Error" class="typography">$ErrorMessage</div><% end_if %>
	<% if GoodMessage %><div id="Error" class="typography">$GoodMessage</div><% end_if %>
	<% if PaymentForm %><div id="Outer" class="typography">$PaymentForm</div><% end_if %>
</body>
 </html>
