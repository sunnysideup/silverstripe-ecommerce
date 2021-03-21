<h1>Debug information for $getRootGroup.Title</h1>


<% with $getRootGroupController %>
<hr /><hr /><h2 style="color: red; padding-top: 2rem">Controller</h2><hr />
<ul>
<li><strong>ID:</strong> $ID</li>
<li><strong>ClassName:</strong> $ClassName</li>
<li><strong>Raw Product List:</strong> $DebugMe(getProductList)</li>
<li><strong>Paginated Product List:</strong> $DebugMe(Products)</li>
<li><strong>Products are Cacheable:</strong> $DebugMe(ProductGroupListAreCacheable)</li>
<li><strong>Products are Cacheable in general:</strong> $DebugMe(productListsHTMLCanBeCached)</li>
<li><strong>Products are Ajaxified:</strong> $DebugMe(ProductGroupListAreAjaxified)</li>
<li><strong>OriginalTitle:</strong> $DebugMe(OriginalTitle)</li>
<li><strong>Menu Child Categories:</strong> $DebugMe(MenuChildGroups)</li>
</ul>

<h4>Show Links?</h4>
<ul>
<li><strong>Show any sort of filter / sort:</strong> $DebugMe(ShowGroupFilterSortDisplayLinks)</li>
<li><strong>Show Group Filter Links:</strong> $DebugMe(ShowGroupFilterLinks)</li>
<li><strong>Show Filters Links:</strong> $DebugMe(ShowFilterLinks)</li>
<li><strong>Show Sort Links:</strong> $DebugMe(ShowSortLinks)</li>
<li><strong>Show Display Links:</strong> $DebugMe(ShowDisplayLinks)</li>
<li><strong>Has Many Products:</strong> $DebugMe(HasManyProducts)</li>
</ul>

<h4>Has ... Available?</h4>
<ul>
<li><strong>Has Group Filters:</strong> $DebugMe(HasGroupFilters)</li>
<li><strong>Has Filters:</strong> $DebugMe(HasFilters)</li>
<li><strong>Has Sorts:</strong> $DebugMe(HasSorts)</li>
<li><strong>Has Displays:</strong> $DebugMe(HasDisplays)</li>
</ul>

<h4>Has ... Right Now?</h4>
<ul>
<li><strong>Has Group Filter:</strong> $DebugMe(HasGroupFilter)</li>
<li><strong>Has Filter:</strong> $DebugMe(HasFilter)</li>
<li><strong>Has Sort:</strong> $DebugMe(HasSort)</li>
<li><strong>Has Display:</strong> $DebugMe(HasDisplay)</li>
<li><strong>Has Any Sort of Filter / Sort:</strong> $DebugMe(HasGroupFilterSortDisplay)</li>
</ul>

<h4>Pagination and Counts</h4>
<ul>
<li><strong>Number of products</strong> $DebugMe(TotalCount)</li>
<li><strong>Current Page Number</strong> $DebugMe(getCurrentPageNumber)</li>
<li><strong>Number of Products per Page</strong> $DebugMe(getProductsPerPage)</li>
<li><strong>IsShowFullList</strong> $DebugMe(IsShowFullList)</li>
<li><strong>Absolute Max Number Of Products Per Page</strong> $DebugMe(MaxNumberOfProductsPerPage)</li>
</ul>

<h4>Preferences</h4>
<ul>
<li><strong>Current Group Filter Title</strong> $DebugMe(getCurrentGroupFilterTitle)</li>
<li><strong>Current Filter Title</strong> $DebugMe(getCurrentFilterTitle)</li>
<li><strong>Current Sort Title</strong> $DebugMe(getCurrentSortTitle)</li>
<li><strong>Current Display Title</strong> $DebugMe(getCurrentDisplayTitle)</li>
<li><strong>Default Display</strong> $DebugMe(MyDefaultDisplayStyle)</li>
</ul>

<h4>Links</h4>
<ul>
<li><strong>Current Link</strong> $DebugMe(Link)</li>
<li><strong>Group Filter Links</strong> $DebugMe(GroupFilterLinks)</li>
<li><strong>Filter Links</strong> $DebugMe(FilterLinks)</li>
<li><strong>Sort Links</strong> $DebugMe(SortLinks)</li>
<li><strong>Display Links</strong> $DebugMe(DisplayLinks)</li>
<li><strong>Default Display</strong> $DebugMe(MyDefaultDisplayStyle)</li>
<li><strong>ListAFewLink</strong> $DebugMe(ListAFewLink)</li>
<li><strong>ListAllLink</strong> $DebugMe(ListAllLink)</li>
</ul>



<% with $getRootGroup %>
<hr /><hr /><h2 style="color: red; padding-top: 2rem">Product Group Data Record Details:</h2><hr />
<ul>
    <li><strong>ID:</strong> $ID</li>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>Number of Products Per Page:</strong> $DebugMe(getProductsPerPage)</li>
    <li><strong>Levels of product child groups to show:</strong> $DebugMe(getMyLevelOfProductsToShow)</li>
    <li><strong>Filter for segment:</strong> $DebugMe(FilterForGroupLinkSegment)</li>
    <li><strong>Search Link</strong> $DebugMe(SearchResultLink)</li>
    <li><strong>Also Show Product Array:</strong> $DebugMe(getProductsToBeIncludedFromOtherGroupsArray)</li>
    <li><strong>ParentGroup:</strong> $DebugMe(ParentGroup)</li>
    <li><strong>Image:</strong> $DebugMe(Image)</li>
    <li><strong>Best Available Image (recursive):</strong> $DebugMe(BestAvailableImage)</li>
    <li><strong>Number of Direct Products:</strong> $DebugMe(getNumberOfProducts)</li>
    <li><strong>getSortFilterDisplayValues:</strong> $DebugMe(getSortFilterDisplayValues)</li>
    <li><strong>getBuyableClassName:</strong> $DebugMe(getBuyableClassName)</li>
    <li><strong>getProductsAlsoInOtherGroups:</strong> $DebugMe(getProductsAlsoInOtherGroups)</li>
    <li><strong>Child Categories (based on products):</strong> $DebugMe(ChildCategoriesBasedOnProducts)</li>
    <li><strong>Child Categories (based on hierarchy):</strong> $DebugMe(ChildCategories)</li>
    <li><strong>Show Levels:</strong> $DebugMe(getShowProductLevelsArray)</li>
</ul>
<% end_with %>

<% with $getUserPreferencesClass %>
<hr /><hr /><h2 style="color: red; padding-top: 2rem">User Specific Details</h2><hr />
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
    <li><strong>use session to remember settings:</strong> $getUseSessionAll</li>
    <li><strong>use session to remember settings for each page:</strong> $getUseSessionPerPageAll</li>
    <li><strong>User Settings:</strong> $getCurrentUserPreferences</li>
    <li><strong>Group Filter:</strong> $getGroupFilterTitle</li>
    <li><strong>Filter:</strong> $getFilterTitle</li>
    <li><strong>Sort:</strong> $getSortTitle</li>
    <li><strong>Display:</strong> $getDisplayTitle</li>
</ul>
<% end_with %>

<% with $getTemplateForProductsAndGroups %>
<hr /><hr /><h2 style="color: red; padding-top: 2rem">Template Provider</h2><hr />
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
<hr /><hr /><h2 style="color: red; padding-top: 2rem">Base List</h2><hr />
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
<hr /><hr /><h2 style="color: red; padding-top: 2rem">Final List</h2><hr />
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% end_with %>




<% end_with %>
