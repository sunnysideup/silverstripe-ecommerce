<div id="CartPage">

	<h1 class="pagetitle">$Title</h1>

	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

	<div id="OrderHolder">
	<% if Order %>
		<% if CanEditOrder %>
			<% control Order %><% include Order_Content_Editable %><% end_control %>
		<% else %>
	<p class="message canNotEdit">Sorry, you can not edit this order.</p>
		<% end_if %>
	<% else %>
	<p class="message canNotView">Sorry, there is no order to view.</p>
	<% end_if %>
	</div>

	<% if ShowCreateAccountForm %><div id="CreateAccountForm">$CreateAccountForm</div><% end_if %>

	<% include CartActionsAndMessages %>
</div>


