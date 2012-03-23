<div id="CartPage">

	<h1 class="pagetitle">$Title</h1>

	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

	<div id="OrderHolder">
	<% if Order %>
		<% if CanEditOrder %>
			<% control Order %><% include Order_Content_Editable %><% end_control %>
		<% else %>
	<p>Sorry, you can not edit this order.</p>
		<% end_if %>
	<% else %>
	<p>Sorry, there is no order to view.</p>
	<% end_if %>
	</div>

	<% include CartActionsAndMessages %>
</div>


