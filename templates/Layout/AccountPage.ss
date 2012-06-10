<div id="AccountPage">
<% if Message %>
	<p id="AccountPageMessage" class="message">$Message</p>
<% end_if %>


<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% if PastOrders %>
	<h3><% _t("Account.PreviousOrders","Previous Orders.") %></h3>
	<table summary="<% _t("Account.PreviousOrders","Previous Orders.") %>">
		<thead>
			<tr>
				<th scope="col" class="left"><% _t("Account.ORDER","Order") %></th>
				<th scope="col" class="left"><% _t("Account.STATUS","Status") %></th>
				<th scope="col" class="right"><% _t("Account.TOTAL","Total") %></th>
				<th scope="col" class="right"><% _t("Account.PAID","Paid") %></th>
				<th scope="col" class="right"><% _t("Account.OUTSTANDING","Outstanding") %></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="col" class="left"><% _t("Account.TOTAL","Total") %></th>
				<th scope="col" class="left"></th>
				<th scope="col" class="right">$RunningTotal.Nice</th>
				<th scope="col" class="right">$RunningPaid.Nice</th>
				<th scope="col" class="right">$RunningOutstanding.Nice</th>
			</tr>
		</tfoot>
		<tbody>
		<% control PastOrders %>
			<tr>
				<td class="left"><a href="$Link">$Title</a></td>
				<td class="left">$CustomerStatus</td>
				<td class="right">$Total.Nice</td>
				<td class="right">$TotalPaid.Nice</td>
				<td class="right">$TotalOutstanding.Nice</td>
			</tr>
		<% end_control %>
		</tbody>
	</table>
<% else %>
	<p class="message good noPreviousOrders"><% _t("Account.NOHISTORY","You do not have any previous orders.") %></p>
<% end_if %>

<% if MemberForm %>
	<div id="MemberForm" class="typography">
		$MemberForm
	</div>
<% end_if %>

</div>



