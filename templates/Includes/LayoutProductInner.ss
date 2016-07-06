<h1 class="pageTitle">$Title</h1>
<div class="productDetails">
<% include ProductImage %>
<% include ProductActions %>
</div>
<% if Content %><div id="ContentHolder">$Content</div><% end_if %>
<% include OtherProductInfo %>
<% if Form %><div id="FormHolder">$Form</div><% end_if %>
