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
						<h2 class="orderHeading">$Title</h2>
						<% if RetrieveLink %><small><a href="$RetrieveLink"><% _t("Order.VIEWONLINE", "view order on website.") %></a></small><% end_if %>
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
