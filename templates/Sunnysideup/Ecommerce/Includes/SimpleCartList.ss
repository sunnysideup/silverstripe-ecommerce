<% with Cart %>
	<% if Items %>
		<% loop Items %>
			<% if ShowInCart %>
<li id="$CartID" class="$Classes $FirstLast orderItemHolder">

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: /images/ (case sensitive)
  * NEW: /client/images/ (COMPLEX)
  * EXP: Check new location, also see: https://docs.silverstripe.org/en/4/developer_guides/templates/requirements/#direct-resource-urls
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
	<a class="ajaxQuantityLink removeFromCart" href="$removeallLink" title="remove"><img src="ecommerce/client/images/remove.gif" alt="x" /></a>
	<% if Link %>
	<a id="$AJAXDefinitions.CartTitleID" href="$Link" class="cartTitle">$CartTitle.LimitWordCount</a>
	<% else %>
	<span id="$AJAXDefinitions.CartTitleID" class="cartTitle">$CartTitle.LimitWordCount</span>
	<% end_if %>
</li>
			<% end_if %>
		<% end_loop %>
<li><a href="$EcomConfig.CheckoutLink" class="shoppingCartLink"><% _t("Order.GOTOCHECKOUTLINK","Go to the checkout") %></a></li>
	<% else %>
<li><% _t("Order.NOITEMS","There are <strong>no</strong> items in your cart.") %></li>
	<% end_if %>
<% end_with %>
