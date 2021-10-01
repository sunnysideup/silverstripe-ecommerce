<div id="$AjaxDefinitions.ProductListHolderID"  class="mainSection content-container withSidebar">
<% if ProductGroupListAreCacheable %>
    <% cached $ProductGroupListCachingKey %>
        <% include Sunnysideup\Ecommerce\Includes\LayoutProductGroupInner %>
    <% end_cached %>
<% else %>
    <% include Sunnysideup\Ecommerce\Includes\LayoutProductGroupInner %>
<% end_if %>


</div>

<aside>
    <div id="Sidebar">
        <div class="sidebarTop"></div>
        <% include Sunnysideup\Ecommerce\Includes\Sidebar_Cart %>
        <% include Sunnysideup\Ecommerce\Includes\Sidebar_Currency %>
        <% include Sunnysideup\Ecommerce\Includes\Sidebar_UserAccount %>
        <% include Sunnysideup\Ecommerce\Includes\Sidebar %>
        <div class="sidebarBottom"></div>
    </div>
</aside>
