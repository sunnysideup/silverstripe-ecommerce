<% with $RootGroupController %>
<h1>Debug information for $Title</h1>

<h2>Product Group Controller Details:</h2>
<ul>
    <li><strong>ID:</strong> $ID</li>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>Number of Products Per Page:</strong> $getProductsPerPage</li>
    <li><strong>Levels of product child groups to show:</strong> $getMyLevelOfProductsToShow</li>
    <li><strong>Filter for segment:</strong> $FilterForGroupLinkSegment</li>
    <li><strong>Child Categories:</strong> $ChildGroups</li>
    <%-- <li><strong>Also Show Product Array:</strong> $getProductsToBeIncludedFromOtherGroupsArray</li> --%>
    <li><strong>ParentGroup:</strong> $ParentGroup</li>
</ul>
<% end_with %>

<% with $RootGroupController %>
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
    <li><strong>List of level options:</strong> $getShowProductLevelsArray</li>
    <li><strong>Excluded Products 1:</strong> $getExcludedProducts</li>
    <li><strong>Excluded Products 2:</strong> $getBlockedProductIds</li>
</ul>
<h3>Products</h3>
<ul>
    <li><strong>Product Ids:</strong> $getProductIds</li>
    <li><strong>Products:</strong> $getProducts</li>
    <li><strong>Raw Count:</strong> $getRawCount</li>
    <li><strong>More than one:</strong> $hasMoreThanOne</li>
    <li><strong>More than ten:</strong> $hasMoreThanOne(10)</li>
    <li><strong>More than a million:</strong> $hasMoreThanOne(99999999)</li>
    <li><strong>Direct Products:</strong> $getDirectProducts</li>
    <li><strong>Direct Products without AlsoShow:</strong> $getDirectProductsExclusingAlsoShow</li>
    <li><strong>Direct Products with AlsoShow:</strong> $getDirectProductsWithAlsoShow</li>
    <li><strong>Direct Products AlsoShow only:</strong> $getAlsoShowProductsFromRootGroupExclusive</li>
    <li><strong>Hierarchy Children:</strong> $getChildProductsInclusive</li>
    <li><strong>Hierarchy Children without AlsoShow:</strong> $getChildProductsExclusive</li>
    <li><strong>AlsoShow IDs:</strong> $getAlsoShowProductsIds</li>
    <li><strong>AlsoShow Products:</strong> $getAlsoShowProducts</li>
    <li><strong>AlsoShow Products without hierary ones:</strong> $getAlsoShowProductsExclusive</li>
</ul>

<h3>Categories</h3>
<ul>
    <li><strong>Filter for candidates IDs:</strong> $getFilterForCandidateCategoryIds</li>
    <li><strong>Filter for candidates:</strong> $getFilterForCandidateCategories</li>
    <li><strong>Category Group IDs based on actual products:</strong> $getParentGroupIdsBasedOnProducts</li>
    <li><strong>Categories based on actual products:</strong> $getParentGroupsBasedOnProducts</li>
    <li><strong>Categories based on actual products, excluding root group:</strong> $getParentGroupsBasedOnProductsExcludingRootGroup</li>

    <li><strong>Direct Child Categories:</strong> $getDirectParentGroupsInclusive</li>
    <li><strong>Direct Child Categories without AlsoShow:</strong> $getDirectParentGroupsExclusive</li>

    <li><strong>Hierarchy Category Ids:</strong> $getParentGroupIds</li>
    <li><strong>Hierarchy Category:</strong> $getParentGroups</li>
    <li><strong>Hierarchy Category without also show Categories:</strong> $getParentGroupsExclusive</li>

    <li><strong>AlsoShow Category Ids related through AlsoShow (i.e. from all the products, what Categories are related through many-many):</strong> $getAlsoShowParentIds</li>
    <li><strong>AlsoShow Categories related through AlsoShow (i.e. from all the products, what Categories are related through many-many):</strong> $getAlsoShowParents</li>

    <li><strong>AlsoShow Product Parents (ie. from the Also Show products, what are the natural parents?) with hierarchy Categories:</strong> $getAlsoShowProductsProductGroupInclusive</li>
    <li><strong>AlsoShow Product Parents (ie. from the Also Show products, what are the natural parents?) without hierarchy Categories:</strong> $getAlsoShowProductsProductGroupsExclusive</li>
</ul>
<% end_with %>

<% with $getFinalProductList %>
<h2>Final List</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% end_with %>




<% end_with %>
