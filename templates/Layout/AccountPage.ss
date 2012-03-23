<div id="AccountPage">
<% if Message %>
	<p id="AccountPageMessage" class="message">
		$Message
	</p>
<% end_if %>

<% if PastOrders %>
<h3>previous orders</h3>
<table summary="PastOrders">
	<thead>
		<tr>
			<th scope="col" class="left">order</th>
			<th scope="col" class="left">status</th>
			<th scope="col" class="right">total</th>
			<th scope="col" class="right">paid</th>
			<th scope="col" class="right">outstanding</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th scope="col" class="left">Total</th>
			<th scope="col" class="left"></th>
			<th scope="col" class="right">$RunningTotal</th>
			<th scope="col" class="right">$RunningPaid</th>
			<th scope="col" class="right">$RunningOutstanding</th>
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
<% end_if %>

<% if MemberForm %>
	<div id="MemberForm" class="typography">
		$MemberForm
	</div>
<% end_if %>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

</div>



