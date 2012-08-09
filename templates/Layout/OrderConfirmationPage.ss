<div id="OrderConfirmationPage" class="mainSection content-container withSidebar">

	<h1 class="pagetitle">$Title</h1>

	<% if CheckoutSteps %><% include CheckoutStepsList %><% end_if %>

	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>


<% if Order %>
	<% control Order %>
		<% include Order %>
	<% end_control %>
	<div id="PaymentForm">$PaymentForm</div>
	<div id="CancelForm">$CancelForm</div>
<% else %>
	<p class="message bad"><% _t("OrderConfirmationPage.COULDNOTBEFOUND","Your order could not be found.") %></p>
<% end_if %>

	<h3><% _t("OrderConfirmation.NEXT", "Next") %></h3>
	<% include CartActionsAndMessages %>

</div>


<aside>
	<div id="Sidebar">
		<div class="sidebarTop"></div>
		<% include Sidebar_UserAccount %>
		<div class="sidebarBottom"></div>
	</div>
</aside>
