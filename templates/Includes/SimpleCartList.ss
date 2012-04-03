<% control Cart %>
	<% if Items %>
		<% control Items %>
			<% if ShowInCart %>
<li id="$CartID" class="$Classes $FirstLast orderItemHolder">
	<a class="ajaxQuantityLink removeFromCart" href="$removeallLink" title="remove"><img src="ecommerce/images/remove.gif" alt="x"/></a>
	<% if Link %>
	<a id="$AJAXDefinitions.CartTitleID" href="$Link" class="cartTitle">$CartTitle.LimitWordCount</a>
	<% else %>
	<span id="$AJAXDefinitions.CartTitleID" class="cartTitle">$CartTitle.LimitWordCount</span>
	<% end_if %>
</li>
			<% end_if %>
		<% end_control %>
<li><a href="$CheckoutLink" class="shoppingCartLink">Go to Shopping Cart</a></li>
	<% else %>
<li>There are no items in your cart</li>
	<% end_if %>
<% end_control %>
