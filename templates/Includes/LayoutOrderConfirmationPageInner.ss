<h1 class="pagetitle">$Title</h1>

<% if CheckoutSteps %><% include CheckoutStepsList %><% end_if %>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>


<% if Order %>
<% with Order %>
	<% include Order %>
<% end_with %>
<div id="PaymentForm">$PaymentForm</div>
<div id="CancelForm">$CancelForm</div>
<% else %>
<p class="message bad"><% _t("OrderConfirmationPage.COULDNOTBEFOUND","Your order could not be found.") %></p>
<% end_if %>

<% include CartActionsAndMessages %>
