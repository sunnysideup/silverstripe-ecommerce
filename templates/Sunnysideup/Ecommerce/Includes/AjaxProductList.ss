<% if ProductGroupListAreCacheable %>...
    <% cached ProductGroupListCachingKey %>
        <% include Sunnysideup\Ecommerce\Includes\LayoutProductGroupInner %>
    <% end_cached %>
<% else %>
    <% include Sunnysideup\Ecommerce\Includes\LayoutProductGroupInner %>
<% end_if %>
