<% include EcommerceAnalytics/Includes/ListViewItemScript %>
<div id="$AjaxDefinitions.HiddenPageTitleID" style="display: none;">$Title</div>
<div id="HiddenPageSubTitle" style="display: none;">$SecondaryTitle</div>
<div id="ProductListItems">
    <% if $Products %>
    <% if $TotalCount > 4 %><div class="product-count-info">$StartLimit - $StopLimit of $TotalCount</div><% end_if %>
        <% if $IsShowFullList %>
            <% include ProductListAllView %>
        <% else %>
            <div class="products-grid">
                <% loop Products %>
                    <% if canPurchase %>
                        <% include ProductCard %>
                    <% end_if  %>
                <% end_loop %>
            </div>
        <% end_if %>

        <div class="managementBox bottomBox $AjaxDefinitions.ProductListAjaxifiedLinkClassName">
            <% include Pagination %>
        </div>

    <% else %>

        <p class="noProducts typography">
                Sorry, no products matched your select <% if $IsSearchResults %>search<% else %>selection<% end_if %>.
                Please <a href="$ResetPreferencesLink">remove any filters</a> to see our product selection.
                If you can not find what you are looking for, then please contact us.
        </p>

        <div class="managementBox noProductsBox topBox"></div>

    <% end_if %>

</div>
