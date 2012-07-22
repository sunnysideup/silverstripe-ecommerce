<div id="AccountPage" class="mainSection content-container withSidebar">

<% if Message %>
	<p id="AccountPageMessage" class="message">$Message</p>
<% end_if %>


<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<div id="PastOrderHolder">
	<h3><% _t("Account.PreviousOrders","Previous Orders") %></h3>
	<% include AccountPastOrders %>
</div>

<% if MemberForm %>
	<div id="MemberForm">
		$MemberForm
	</div>
<% end_if %>

</div>


<aside>
	<div id="Sidebar">
		<div class="sidebarTop"></div>
		<% include Sidebar_Currency %>
		<div class="sidebarBottom"></div>
	</div>
</aside>

