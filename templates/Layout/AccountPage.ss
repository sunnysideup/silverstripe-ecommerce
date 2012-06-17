<div id="AccountPage">
<% if Message %>
	<p id="AccountPageMessage" class="message">$Message</p>
<% end_if %>


<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<% include AccountPastOrders %>

<% if MemberForm %>
	<div id="MemberForm">
		$MemberForm
	</div>
<% end_if %>

</div>



