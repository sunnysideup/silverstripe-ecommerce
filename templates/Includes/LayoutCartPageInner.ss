<h1 class="pagetitle">$Title</h1>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<div id="OrderHolder">
<% if Order %>
	<% if CanEditOrder %>
		<% with Order %><% include Order_Content_Editable %><% end_with %>
	<% end_if %>
<% else %>
<div class="message bad canNotView">$NonExistingOrderMessage</p>
<% end_if %>
</div>

<% if ShowCreateAccountForm %><div id="CreateAccountForm">$CreateAccountForm</div><% end_if %>

<% include CartActionsAndMessages %>
