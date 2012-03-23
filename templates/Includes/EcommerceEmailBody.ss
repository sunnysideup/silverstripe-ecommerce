<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
<title>$Subject</title>
</head>
<body>
<div id="EmailContent">
	<table id="Content" cellspacing="0" cellpadding="0" summary="Email Information">
		<thead>
			<% if EmailLogo %>
			<tr>
				<th>
					<img src="$EmailLogo.getAbsoluteURL" alt="Logo - $EmailLogo.Title" />
				</th>
			</tr>
			<% end_if %>
			<tr>
				<th>
					<h1 class="title">$Subject</h1>
					<% if Message %>$Message<% end_if %>
				</th>
			</tr>
		</thead>
		<tbody>
<% if Order %>
			<tr>
				<td>
					<% control Order %>
					<div id="OrderInformation">
						<h2 class="orderHeading"><a href="$RetrieveLink">$Title</a></h2>
						<% include Order_Addresses %>
						<% include Order_Content %>
						<% include Order_Payments %>
						<% include Order_OutstandingTotal %>
						<% include Order_OrderStatusLogs %>
						<% include Order_CustomerNote %>
					</div>
<% require themedCSS(Order) %>
<% require themedCSS(Order_Print, print) %>



					<% end_control %>
				</td>
			</tr>
<% end_if %>
		</tbody>
	</table>
</div>
</body>
</html>
