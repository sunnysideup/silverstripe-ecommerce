

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<div class="filterSortDisplayAndSearch">
<% include Sunnysideup\Ecommerce\Includes\ProductGroupFilterSortSearchHeaders %>
<% include Sunnysideup\Ecommerce\Includes\ProductGroupDisplayOptions %>
<% include Sunnysideup\Ecommerce\Includes\ProductGroupFilterOptions %>
<% include Sunnysideup\Ecommerce\Includes\ProductGroupChildGroups %>
<% include Sunnysideup\Ecommerce\Includes\ProductGroupSearchForm %>
</div>

<% if $Products %>
<div id="Products" class="category">

    <div class="resultsBar">
    <% include Sunnysideup\Ecommerce\Includes\ProductGroupSortAndList %>
    </div>

    <ul class="productList displayStyle$MyDefaultDisplayStyle">
    <% if MyDefaultDisplayStyle = Short %><% loop Products %><% include Sunnysideup\Ecommerce\Includes\ProductGroupItemShort %><% end_loop %>
    <% else_if MyDefaultDisplayStyle = MoreDetail %><% loop Products %><% include Sunnysideup\Ecommerce\Includes\ProductGroupItemMoreDetail %><% end_loop %>
    <% else %><% loop Products %><% include Sunnysideup\Ecommerce\Includes\ProductGroupItem %><% end_loop %>
    <% end_if %>
    </ul>
</div>

<% include Sunnysideup\Ecommerce\Includes\ProductGroupPagination %>

<% else %>
<p class="noProductsFound message warning"><% _t("Product.NOPRODUCTSFOUND", "No products are listed here.") %></p>
<% end_if %>

<% if Form %><div id="FormHolder">$Form</div><% end_if %>
