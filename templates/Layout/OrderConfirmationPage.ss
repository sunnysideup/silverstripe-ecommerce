<div id="OrderConformation">

	<h1 class="pagetitle">$Title</h1>

	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

	
<% if Order %>
	<% control Order %>
		<% include Order %>
	<% end_control %>
	<div id="PaymentForm">$PaymentForm</div>
	<div id="CancelForm">$CancelForm</div>


<% else %>
	<% if AllMemberOrders %>
	<div id="PastOrders">
		<h3 class="formHeading"><% _t("OrderConfirmation.HISTORY","Your Order History") %></h3>
		<% control AllMemberOrders %>
		<h4>$Heading</h4>
		<ul>
			<% control Orders %><li><a href="$Link">$Title</a></li><% end_control %>
		</ul>
		<% end_control %>
	</div>
	<% else %>
		$YouDontHaveSavedOrders
	<% end_if %>
<% end_if %>

	<% include CartActionsAndMessages %>

</div>

