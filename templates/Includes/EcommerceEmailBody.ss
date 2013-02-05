<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" >
<title>$Subject</title>
</head>
<body>
<div id="EmailContent">
	<table id="Content" cellspacing="0" cellpadding="0" summary="Email Information">
		<thead>
			<tr>
				<th>
					<% include Order_ShopInfo %>
				</th>
			</tr>

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
						<h2 class="orderHeading"><% if RetrieveLink %><a href="$RetrieveLink"><% end_if %>$Title<% if RetrieveLink %></a><% end_if %></h2>
						<% include Order_OrderStatusLogs %>
						<% include Order_CustomerNote %>
						<% include Order_Addresses %>
						<% include Order_Content %>
						<% include Order_Payments %>
						<% include Order_OutstandingTotal %>
					</div>
					<% end_control %>
				</td>
			</tr>
<% else %>
<p>There was an error in retrieving this order. Please contact the store.</p>
<% end_if %>
		</tbody>
	</table>
</div>
</body>
</html>
