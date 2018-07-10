<h1 class="pageTitle">$Title</h1>

<% if Message %>
	<p id="AccountPageMessage" class="message">$Message</p>
<% end_if %>


<% if Content %><div id="ContentHolder">$Content</div><% end_if %>


<% if MemberForm %>
	<div id="MemberForm">
		$MemberForm
	</div>
<% end_if %>

<% if CurrentMember  %>
    <div id="PastOrderHolder">
    	<h3><% _t("Account.PreviousOrders","Previous Orders") %></h3>
    	<% include AccountPastOrders %>
    </div>
<% end_if %>