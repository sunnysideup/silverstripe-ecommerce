<h1>Debug information for $getRootGroup.Title</h1>
<ul>
<li><a href="#Controller">Controller</a></li>
<li><a href="#ProductGroup">Model</a></li>
<li><a href="#SchemaProvider">Schema Provider</a>: KEY PLAYER - provides the classes and the basic set up for product selection</li>
<li><a href="#BaseList">Base List</a>: the basic list of products available</li>
<li><a href="#FinalList">Final List</a>: the sorted / filtered list</li>
<li><a href="#UserDetails">User Specific Details</a></li>
<li><a href="#Search">Search </a></li>
</ul>

<% with $getRootGroupController %>
<hr /><hr /><h2 id="Controller" style="color: red; padding-top: 2rem">Controller</h2><hr />
<ul>
<li><strong>ID:</strong> $ID</li>
<li><strong>ClassName:</strong> $ClassName</li>
<li><strong>Raw Product List:</strong> $VardumpMe(getProductList)</li>
<li><strong>Products are Cacheable:</strong> $VardumpMe(ProductGroupListAreCacheable)</li>
<li><strong>Products are Cacheable in general:</strong> $VardumpMe(productListsHTMLCanBeCached)</li>
<li><strong>Products are Ajaxified:</strong> $VardumpMe(ProductGroupListAreAjaxified)</li>
<li><strong>OriginalTitle:</strong> $VardumpMe(OriginalTitle)</li>
<li><strong>Menu Child Categories:</strong> $VardumpMe(MenuChildGroups)</li>
<li><strong>MetaTitle (check for secondary title):</strong> $Title $MenuTitle</li>
</ul>

<h4>Show Links?</h4>
<ul>
<li><strong>Show any sort of filter / sort:</strong> $VardumpMe(ShowGroupFilterSortDisplayLinks)</li>
<li><strong>Show Search Filter Links:</strong> $VardumpMe(ShowSearchFilterLinks)</li>
<li><strong>Show Group Filter Links:</strong> $VardumpMe(ShowGroupFilterLinks)</li>
<li><strong>Show Filters Links:</strong> $VardumpMe(ShowFilterLinks)</li>
<li><strong>Show Sort Links:</strong> $VardumpMe(ShowSortLinks)</li>
<li><strong>Show Display Links:</strong> $VardumpMe(ShowDisplayLinks)</li>
<li><strong>Has Many Products:</strong> $VardumpMe(HasManyProducts)</li>
</ul>

<h4>Has ... Available?</h4>
<ul>
<li><strong>Has Search Filters:</strong> $VardumpMe(HasSearchFilters)</li>
<li><strong>Has Group Filters:</strong> $VardumpMe(HasGroupFilters)</li>
<li><strong>Has Filters:</strong> $VardumpMe(HasFilters)</li>
<li><strong>Has Sorts:</strong> $VardumpMe(HasSorts)</li>
<li><strong>Has Displays:</strong> $VardumpMe(HasDisplays)</li>
</ul>

<h4>Has ... Right Now?</h4>
<ul>
<li><strong>Is Search Results:</strong> $VardumpMe(IsSearchResults)</li>
<li><strong>Has Search Filter:</strong> $VardumpMe(HasSearchFilter)</li>
<li><strong>Has Group Filter:</strong> $VardumpMe(HasGroupFilter)</li>
<li><strong>Has Filter:</strong> $VardumpMe(HasFilter)</li>
<li><strong>Has Sort:</strong> $VardumpMe(HasSort)</li>
<li><strong>Has Display:</strong> $VardumpMe(HasDisplay)</li>
<li><strong>Has Any Sort of Filter / Sort:</strong> $VardumpMe(HasGroupFilterSortDisplay)</li>
</ul>

<h4>Pagination and Counts</h4>
<ul>
<li><strong>Number of products</strong> $VardumpMe(TotalCount)</li>
<li><strong>Current Page Number</strong> $VardumpMe(getCurrentPageNumber)</li>
<li><strong>Number of Products per Page</strong> $VardumpMe(getProductsPerPage)</li>
<li><strong>IsShowFullList</strong> $VardumpMe(IsShowFullList)</li>
<li><strong>Absolute Max Number Of Products Per Page</strong> $VardumpMe(MaxNumberOfProductsPerPage)</li>
</ul>

<h4>Preferences</h4>
<ul>
<li><strong>Current Search Filter Title</strong> $VardumpMe(getCurrentSearchFilterTitle)</li>
<li><strong>Current Group Filter Title</strong> $VardumpMe(getCurrentGroupFilterTitle)</li>
<li><strong>Current Filter Title</strong> $VardumpMe(getCurrentFilterTitle)</li>
<li><strong>Current Sort Title</strong> $VardumpMe(getCurrentSortTitle)</li>
<li><strong>Current Display Title</strong> $VardumpMe(getCurrentDisplayTitle)</li>
<li><strong>Default Display</strong> $VardumpMe(MyDefaultDisplayStyle)</li>
</ul>

<h4>Links</h4>
<ul>
<li><strong>Current Link</strong> $VardumpMe(Link)</li>
<li><strong>Search Filter Links</strong> $VardumpMe(SearchFilterLinks)</li>
<li><strong>Group Filter Links</strong> $VardumpMe(GroupFilterLinks)</li>
<li><strong>Filter Links</strong> $VardumpMe(FilterLinks)</li>
<li><strong>Sort Links</strong> $VardumpMe(SortLinks)</li>
<li><strong>Display Links</strong> $VardumpMe(DisplayLinks)</li>
<li><strong>Default Display</strong> $VardumpMe(MyDefaultDisplayStyle)</li>
<li><strong>ListAFewLink</strong> $VardumpMe(ListAFewLink)</li>
<li><strong>ListAllLink</strong> $VardumpMe(ListAllLink)</li>
</ul>


<% end_with %>

<% with $getRootGroup %>
<hr /><hr /><h2 id="ProductGroup" style="color: red; padding-top: 2rem">Product Group Data Record Details:</h2><hr />
<ul>
    <li><strong>ID:</strong> $ID</li>
    <li><strong>ClassName:</strong> $VardumpClassName</li>
    <li><strong>Number of Products Per Page:</strong> $VardumpMe(getProductsPerPage)</li>
    <li><strong>Levels of product child groups to show:</strong> $VardumpMe(getMyLevelOfProductsToShow)</li>
    <li><strong>Filter for segment:</strong> $VardumpMe(FilterForGroupSegment)</li>
    <li><strong>Search Link</strong> $VardumpMe(SearchResultLink)</li>
    <li><strong>Also Show Product Array:</strong> $VardumpMe(getProductsToBeIncludedFromOtherGroupsArray)</li>
    <li><strong>ParentGroup:</strong> $VardumpMe(ParentGroup)</li>
    <li><strong>Image:</strong> $VardumpMe(Image)</li>
    <li><strong>Best Available Image (recursive):</strong> $VardumpMe(BestAvailableImage)</li>
    <li><strong>Number of Direct Products:</strong> $VardumpMe(getNumberOfProducts)</li>
    <li><strong>getSortFilterDisplayValues:</strong> $VardumpMe(getSortFilterDisplayValues)</li>
    <li><strong>getBuyableClassName:</strong> $VardumpMe(getBuyableClassName)</li>
    <li><strong>getProductsAlsoInOtherGroups:</strong> $VardumpMe(getProductsAlsoInOtherGroups)</li>
    <li><strong>Child Categories (based on products):</strong> $VardumpMe(ChildCategoriesBasedOnProducts)</li>
    <li><strong>Child Categories (based on hierarchy):</strong> $VardumpMe(ChildCategories)</li>
    <li><strong>Show Levels:</strong> $VardumpMe(getShowProductLevelsArray)</li>
</ul>
<% end_with %>

<% with $getRootGroupController %>

<% with $getProductGroupSchema %>
<hr /><hr /><h2 id="SchemaProvider" style="color: red; padding-top: 2rem">Schema Provider</h2><hr />
<ul>
    <li><strong>ClassName:</strong> $VardumpClassName</li>
    <li><strong>Data:</strong> $getData</li>
    <li><strong>Search Filter Options:</strong> $getSearchFilterOptionsMap</li>
    <li><strong>Group Filter Options:</strong> $getGroupFilterOptionsMap</li>
    <li><strong>Filter Options:</strong> $getFilterOptionsMap</li>
    <li><strong>Sort Options:</strong> $getSortOptionsMap</li>
    <li><strong>Display Options:</strong> $getDisplayOptionsMap</li>
</ul>
<% end_with %>

<% with $getBaseProductList %>
<hr /><hr /><h2 id="BaseList" style="color: red; padding-top: 2rem">Base List</h2><hr />
<p>
Accessed by: <strong>ProductGroup.getBaseProductList()</strong>.
</p>
<ul>
    <li><strong>ClassName:</strong> $VardumpClassName</li>
    <li><strong>Product (Buyable) Class Name:</strong> $getBuyableClassName</li>
    <li><strong>Children to show (levels):</strong> $getLevelOfProductsToShow</li>
    <li><strong>List of level options:</strong> $getShowProductLevelsArray</li>
    <li><strong>Excluded Products 1:</strong> $getExcludedProducts</li>
    <li><strong>Excluded Products 2:</strong> $getBlockedProductIds</li>
    <li><strong>Product Count:</strong> $getProductIds</li>
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
<hr /><hr /><h2 id="FinalList" style="color: red; padding-top: 2rem">Final List</h2><hr />
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

<% end_with %>

<% with $getUserPreferencesClass %>
<hr /><hr /><h2 id="UserDetails" style="color: red; padding-top: 2rem">User Specific Details</h2><hr />
<ul>
    <li><strong>ClassName:</strong> $VardumpClassName</li>
    <li><strong>use session to remember settings:</strong> $getUseSessionAll</li>
    <li><strong>use session to remember settings for each page:</strong> $getUseSessionPerPageAll</li>
    <li><strong>User Settings:</strong> $getCurrentUserPreferences</li>
    <li><strong>Search Filter:</strong> $getSearchFilterTitle</li>
    <li><strong>Group Filter:</strong> $getGroupFilterTitle</li>
    <li><strong>Filter:</strong> $getFilterTitle</li>
    <li><strong>Sort:</strong> $getSortTitle</li>
    <li><strong>Display:</strong> $getDisplayTitle</li>
</ul>
<% end_with %>

<hr /><hr /><h2 id="FinalList" style="color: red; padding-top: 2rem">Search</h2><hr />
<ul>
<li><strong>Debug:</strong> $DebugSearchString</li>
</ul>

<% end_with %>
