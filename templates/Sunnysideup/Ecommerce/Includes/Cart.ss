<% if Cart %><% with Cart %>
<div id="ShoppingCart">
    <h3 id="CartHeader"><% _t("CART","Cart") %></h3>
    <% include Sunnysideup\Ecommerce\Includes\Sidebar_Cart_Inner %>
</div>
<% end_with %><% end_if %>



<% include Sunnysideup\Ecommerce\Includes\ShoppingCartRequirements %>
