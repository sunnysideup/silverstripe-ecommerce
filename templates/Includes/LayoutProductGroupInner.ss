<h1 class="pageTitle" id="$AjaxDefinitions.HiddenPageTitleID">$Title</h1>

<% if Content %><div id="ContentHolder">$Content</div><% end_if %>

<div class="filterSortDisplayAndSearch">
<% include ProductGroupFilterSortSearchHeaders %>
<% include ProductGroupDisplayOptions %>
<% include ProductGroupFilterOptions %>
<% include ProductGroupChildGroups %>
<% include ProductGroupSearchForm %>
</div>

<% if $Products %>
<div id="Products" class="category">

    <div class="resultsBar">
    <% include ProductGroupSortAndList %>
    </div>

    <ul class="productList displayStyle$MyDefaultDisplayStyle <% if IsShowFullList %>fullList<% end_if %>">
    <% if IsShowFullList %>
        <% loop Products %><li><div class="fullListRow"><a href="$Link">$Title</a><span class="price">$CalculatedPriceAsMoney.NiceDefaultFormat</span></div></li><% end_loop %>
    <% else_if MyDefaultDisplayStyle = Short %><% loop Products %><% include ProductGroupItemShort %><% end_loop %>
    <% else_if MyDefaultDisplayStyle = MoreDetail %><% loop Products %><% include ProductGroupItemMoreDetail %><% end_loop %>
    <% else %><% loop Products %><% include ProductGroupItem %><% end_loop %>
    <% end_if %>
    </ul>
</div>

<% include ProductGroupPagination %>

<% else %>
<p class="noProductsFound message warning"><% _t("Product.NOPRODUCTSFOUND", "No products are listed here.") %></p>
<% end_if %>

<% if Form %><div id="FormHolder">$Form</div><% end_if %>
