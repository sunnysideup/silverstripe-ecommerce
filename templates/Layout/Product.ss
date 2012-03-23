<div id="Sidebar">
	<div class="sidebarTop"></div>
	<% include Sidebar_Cart %>
	<% include Sidebar %>
	<div class="sidebarBottom"></div>
</div>
<div id="Product" class="mainSection">
	<h1 class="pageTitle">$Title</h1>
	<div class="productDetails">
<% include ProductImage %>
<% include ProductActions %>
	</div>
	<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include OtherProductInfo %>
	<% if Form %><div id="FormHolder">$Form</div><% end_if %>
	<% if PageComments %><div id="PageCommentsHolder">$PageComments</div><% end_if %>
</div>




