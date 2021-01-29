<% with $RootGroupController %>
<h1>Debug information for $Title</h1>
<h2>Base Information:</h2>
<ul>
    <li><strong>ID:</strong> $ID</li>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% with $getTemplateForProductsAndGroups %>
<h2>Template Provider</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>Data:</strong> $getData</li>
    <li><strong>Group Filter Options:</strong> $getGroupFilterOptionsMap</li>
    <li><strong>Filter Options:</strong> $getFilterOptionsMap</li>
    <li><strong>Sort Options:</strong> $getSortOptionsMap</li>
    <li><strong>Display Options:</strong> $getDisplayOptionsMap</li>
</ul>
<% end_with %>

<% with $getBaseProductList %>
<h2>Base List</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>Product (Buyable) Class Name:</strong> $getBuyableClassName</li>
    <li><strong>Children to show (levels):</strong> $getLevelOfProductsToShow</li>
    <li><strong>Product Ids:</strong> $getProductIds</li>
    <li><strong>Also show IDs:</strong> $getAlsoShowProductsIds</li>
    <li><strong>Parent Group IDs:</strong> $getParentGroupIds</li>
    <%-- <li>Groups:<% with $getGroups %><ul><% loop $getGroups %><li>$MenuTitle</li><% end_loop %></ul><% end_with %></li> --%>

</ul>
<% end_with %>

<h2>Final List</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<h2>Group Filters</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<h2>Filters</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<h2>Sorters</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<h2>Displayers</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% end_with %>
