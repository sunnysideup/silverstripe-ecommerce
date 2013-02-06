<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>$Subject</title>
</head>
<body style="margin: 20px">
<div id="EmailContent">
	<table id="Content" cellspacing="0" cellpadding="0" summary="Email Information">
		<thead>
			<tr class="shopAddress">
				<th>
					<% include Order_ShopInfo %>
				</th>
			</tr>

			<tr class="message">
				<td class="left">
					<h1 class="title">$Subject</h1>
					<% if Message %><div class="message">$Message</div><% end_if %>
					<% if OrderStepMessage %><div class="orderStepMessage">$OrderStepMessage.RAW</div><% end_if %>
				</td>
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
