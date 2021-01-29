<% with $RootGroupController %>
<h1>Debug information for $Title</h1>

<h2>Product Group Controller Details:</h2>
<ul>
    <li><strong>ID:</strong> $ID</li>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% with $getUserPreferencesClass %>
<h2>User Specific Details</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>use session to remember settings:</strong> $getUseSessionAll</li>
    <li><strong>use session to remember settings for each page:</strong> $getUseSessionPerPageAll</li>
    <li><strong>User Settings:</strong> $getCurrentUserPreferences</li>
    <li><strong>Group Filter:</strong> $getGroupFilterTitle</li>
    <li><strong>Filter:</strong> $getFilterTitle</li>
    <li><strong>Sort:</strong> $getSortTitle</li>
    <li><strong>Display:</strong> $getDisplayTitle</li>
    <li><strong>Group Links:</strong> $getLinksPerType(GROUPFILTER)</li>
    <li><strong>Filter Links:</strong> $getLinksPerType(FILTER)</li>
    <li><strong>Sort Links:</strong> $getLinksPerType(SORT)</li>
    <li><strong>Display Links:</strong> $getLinksPerType(DISPLAY)</li>
</ul>
<% end_with %>

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
</ul>
<h3>Products</h3>
<ul>
    <li><strong>Product Ids:</strong> $getProductIds</li>
    <li><strong>Excluded Products 1:</strong> $getExcludedProducts</li>
    <li><strong>Excluded Products 2:</strong> $getBlockedProductIds</li>
    <li><strong>AlsoShow IDs:</strong> $getAlsoShowProductsIds</li>
    <li><strong>Raw Count:</strong> $getRawCount</li>
    <li><strong>More than one:</strong> $hasMoreThanOne</li>
    <li><strong>More than ten:</strong> $hasMoreThanOne(10)</li>
    <li><strong>More than a million:</strong> $hasMoreThanOne(99999999)</li>
    <li><strong>Direct Products:</strong> $getDirectProducts</li>
    <li><strong>Direct Products (AlsoShow products removed):</strong> $getDirectProductsWithAlsoShow</li>
    <li><strong>Child Products:</strong> $getChildProductsInclusive</li>
    <li><strong>Child Products (AlsoShow products removed):</strong> $getChildProductsExclusive</li>
    <li><strong>AlsoShow:</strong> $getAlsoShowProductsInclusive</li>
    <li><strong>AlsoShow (child products removed):</strong> $getAlsoShowProductsExclusive</li>
    <li><strong>AlsoShow (root group only):</strong> $getAlsoShowProductsFromRootGroupOnly</li>
</ul>

<h3>Parents</h3>
<ul>
    <li><strong>Parent Group IDs (based on actual products):</strong> $getParentGroupIds</li>
    <li><strong>Parent Groups (based on actual products):</strong> $getParentGroups</li>
    <li><strong>Parent Groups (based on included groups - excluded AlsoShow parents):</strong> $getDirectParentGroupsExclusive</li>
    <li><strong>AlsoShow parents (inc. hierarchy parents):</strong> $getAlsoShowProductsProductGroupInclusive</li>
    <li><strong>AlsoShow parents (exc. hierarchy parents):</strong> $getAlsoShowProductsProductGroupsExclusive</li>
    <li><strong>Related AlsoShow Parents (IDs):</strong> $getAlsoShowParentIds</li>
    <li><strong>Related AlsoShow Parents:</strong> $getAlsoShowParents</li>
</ul>
    <%-- <li><strong>Direct Products ($getDirectProducts.Count):</strong> <ul><% loop $getDirectProducts %><li>$Title</li><% end_loop %></ul></li> --%>
    <%-- <li>Groups:<% with $getGroups %><ul><% loop $getGroups %><li>$MenuTitle</li><% end_loop %></ul><% end_with %></li> --%>

</ul>
<% end_with %>

<% with $getFinalProductList %>
<h2>Final List</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<h3>Products</h3>
<ul>
    <li><strong>Product Ids:</strong> $getProductIds</li>
    <li><strong>AlsoShow IDs:</strong> $getAlsoShowProductsIds</li>
    <li><strong>Raw Count:</strong> $getRawCount</li>
    <li><strong>More than one:</strong> $hasMoreThanOne</li>
    <li><strong>More than ten:</strong> $hasMoreThanOne(10)</li>
    <li><strong>More than a million:</strong> $hasMoreThanOne(99999999)</li>
    <li><strong>Direct Products:</strong> $getDirectProducts</li>
    <li><strong>Direct Products (AlsoShow products removed):</strong> $getDirectProductsWithAlsoShow</li>
    <li><strong>Child Products:</strong> $getChildProductsInclusive</li>
    <li><strong>Child Products (AlsoShow products removed):</strong> $getChildProductsExclusive</li>
    <li><strong>AlsoShow:</strong> $getAlsoShowProductsInclusive</li>
    <li><strong>AlsoShow (child products removed):</strong> $getAlsoShowProductsExclusive</li>
    <li><strong>AlsoShow (root group only):</strong> $getAlsoShowProductsFromRootGroupOnly</li>
</ul>

<h3>Parents</h3>
<ul>
    <li><strong>Parent Group IDs:</strong> $getParentGroupIds</li>
    <li><strong>Parent Groups (based on actual products):</strong> $getParentGroups</li>
    <li><strong>Parent Groups (excluded AlsoShow parents):</strong> $getDirectParentGroupsExclusive</li>
    <li><strong>AlsoShow parents (inc. normal parents):</strong> $getAlsoShowProductsProductGroupInclusive</li>
    <li><strong>AlsoShow parents (exc. normal parents):</strong> $getAlsoShowProductsProductGroupsExclusive</li>
    <li><strong>Related AlsoShow Parents (IDs):</strong> $getAlsoShowParentIds</li>
    <li><strong>Related AlsoShow Parents:</strong> $getAlsoShowParents</li>
</ul>
<% end_with %>

<h2>Group Filters</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
    <%-- <li><strong>Standard Levels to Show:</strong> $getShowProductLevelsArray</li> --%>
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
