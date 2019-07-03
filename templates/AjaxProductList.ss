<% if ProductGroupListAreCacheable %>...
    <% cached ProductGroupListCachingKey %>
        <% include LayoutProductGroupInner %>
    <% end_cached %>
<% else %>
    <% include LayoutProductGroupInner %>
<% end_if %>
