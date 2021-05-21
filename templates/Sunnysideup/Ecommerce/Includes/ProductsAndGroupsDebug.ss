<style>
    ul {
        max-width: 900px;
        background-color: #eee;
    }
    .answer {
        display: none;
    }
    ul:hover .answer {
        display: block;
    }
</style>

<h1>Debug information for $getRootGroup.Title</h1>
<ul>
<li><a href="#Controller">Controller</a></div></li>
<li><a href="#ProductGroup">Model</a></div></li>
<li><a href="#SchemaProvider">Schema Provider</a>: KEY PLAYER - provides the classes and the basic set up for product selection</div></li>
<li><a href="#BaseList">Base List</a>: the basic list of products available</div></li>
<li><a href="#FinalList">Final List</a>: the sorted / filtered list</div></li>
<li><a href="#UserDetails">User Specific Details</a></div></li>
<li><a href="#Search">Search </a></div></li>
</ul>

<% with $getRootGroupController %>
<hr /><hr /><h2 id="Controller" style="color: red; padding-top: 2rem">Controller</h2><hr />
<ul>
<li><strong>ID:</strong> <div class="answer">$ID</div></div></li>
<li><strong>ClassName:</strong> <div class="answer">$ClassName</div></li>
<li><strong>Raw Product List:</strong> <div class="answer">$VardumpMe(getProductList)</div></li>
<li><strong>Products are Cacheable:</strong> <div class="answer">$VardumpMe(ProductGroupListAreCacheable)</div></li>
<li><strong>Products are Cacheable in general:</strong> <div class="answer">$VardumpMe(productListsHTMLCanBeCached)</div></li>
<li><strong>Products are Ajaxified:</strong> <div class="answer">$VardumpMe(ProductGroupListAreAjaxified)</div></li>
<li><strong>OriginalTitle:</strong> <div class="answer">$VardumpMe(OriginalTitle)</div></li>
<li><strong>Menu Child Categories:</strong> <div class="answer">$VardumpMe(MenuChildGroups)</div></li>
<li><strong>MetaTitle (check for secondary title):</strong> <div class="answer">$Title $MenuTitle</div></li>
</ul>

<h4>Show Links?</h4>
<ul>
<li><strong>Show any sort of filter / sort:</strong> <div class="answer">$VardumpMe(ShowGroupFilterSortDisplayLinks)</div></li>
<li><strong>Show Search Filter Links:</strong> <div class="answer">$VardumpMe(ShowSearchFilterLinks)</div></li>
<li><strong>Show Group Filter Links:</strong> <div class="answer">$VardumpMe(ShowGroupFilterLinks)</div></li>
<li><strong>Show Filters Links:</strong> <div class="answer">$VardumpMe(ShowFilterLinks)</div></li>
<li><strong>Show Sort Links:</strong> <div class="answer">$VardumpMe(ShowSortLinks)</div></li>
<li><strong>Show Display Links:</strong> <div class="answer">$VardumpMe(ShowDisplayLinks)</div></li>
<li><strong>Has Many Products:</strong> <div class="answer">$VardumpMe(HasManyProducts)</div></li>
</ul>

<h4>Has ... Available?</h4>
<ul>
<li><strong>Has Search Filters:</strong> <div class="answer">$VardumpMe(HasSearchFilters)</div></li>
<li><strong>Has Group Filters:</strong> <div class="answer">$VardumpMe(HasGroupFilters)</div></li>
<li><strong>Has Filters:</strong> <div class="answer">$VardumpMe(HasFilters)</div></li>
<li><strong>Has Sorts:</strong> <div class="answer">$VardumpMe(HasSorts)</div></li>
<li><strong>Has Displays:</strong> <div class="answer">$VardumpMe(HasDisplays)</div></li>
</ul>

<h4>Has ... Right Now?</h4>
<ul>
<li><strong>Is Search Results:</strong> <div class="answer">$VardumpMe(IsSearchResults)</div></li>
<li><strong>Has Search Filter:</strong> <div class="answer">$VardumpMe(HasSearchFilter)</div></li>
<li><strong>Has Group Filter:</strong> <div class="answer">$VardumpMe(HasGroupFilter)</div></li>
<li><strong>Has Filter:</strong> <div class="answer">$VardumpMe(HasFilter)</div></li>
<li><strong>Has Sort:</strong> <div class="answer">$VardumpMe(HasSort)</div></li>
<li><strong>Has Display:</strong> <div class="answer">$VardumpMe(HasDisplay)</div></li>
<li><strong>Has Any Sort of Filter / Sort:</strong> <div class="answer">$VardumpMe(HasGroupFilterSortDisplay)</div></li>
</ul>

<h4>Pagination and Counts</h4>
<ul>
<li><strong>Number of products</strong> <div class="answer">$VardumpMe(TotalCount)</div></li>
<li><strong>Current Page Number</strong> <div class="answer">$VardumpMe(getCurrentPageNumber)</div></li>
<li><strong>Number of Products per Page</strong> <div class="answer">$VardumpMe(getProductsPerPage)</div></li>
<li><strong>IsShowFullList</strong> <div class="answer">$VardumpMe(IsShowFullList)</div></li>
<li><strong>Absolute Max Number Of Products Per Page</strong> <div class="answer">$VardumpMe(MaxNumberOfProductsPerPage)</div></li>
</ul>

<h4>Preferences</h4>
<ul>
<li><strong>Current Search Filter Title</strong> <div class="answer">$VardumpMe(getCurrentSearchFilterTitle)</div></li>
<li><strong>Current Group Filter Title</strong> <div class="answer">$VardumpMe(getCurrentGroupFilterTitle)</div></li>
<li><strong>Current Filter Title</strong> <div class="answer">$VardumpMe(getCurrentFilterTitle)</div></li>
<li><strong>Current Sort Title</strong> <div class="answer">$VardumpMe(getCurrentSortTitle)</div></li>
<li><strong>Current Display Title</strong> <div class="answer">$VardumpMe(getCurrentDisplayTitle)</div></li>
<li><strong>Default Display</strong> <div class="answer">$VardumpMe(MyDefaultDisplayStyle)</div></li>
</ul>

<h4>Links</h4>
<ul>
<li><strong>Current Link</strong> <div class="answer">$VardumpMe(Link)</div></li>
<li><strong>Search Filter Links</strong> <div class="answer">$VardumpMe(SearchFilterLinks)</div></li>
<li><strong>Group Filter Links</strong> <div class="answer">$VardumpMe(GroupFilterLinks)</div></li>
<li><strong>Filter Links</strong> <div class="answer">$VardumpMe(FilterLinks)</div></li>
<li><strong>Sort Links</strong> <div class="answer">$VardumpMe(SortLinks)</div></li>
<li><strong>Display Links</strong> <div class="answer">$VardumpMe(DisplayLinks)</div></li>
<li><strong>Default Display</strong> <div class="answer">$VardumpMe(MyDefaultDisplayStyle)</div></li>
<li><strong>ListAFewLink</strong> <div class="answer">$VardumpMe(ListAFewLink)</div></li>
<li><strong>ListAllLink</strong> <div class="answer">$VardumpMe(ListAllLink)</div></li>
</ul>


<% end_with %>

<% with $getRootGroup %>
<hr /><hr /><h2 id="ProductGroup" style="color: red; padding-top: 2rem">Product Group Data Record Details:</h2><hr />
<ul>
    <li><strong>ID:</strong> <div class="answer">$ID</div></li>
    <li><strong>ClassName:</strong> <div class="answer">$VardumpClassName</div></li>
    <li><strong>Number of Products Per Page:</strong> <div class="answer">$VardumpMe(getProductsPerPage)</div></li>
    <li><strong>Levels of product child groups to show:</strong> <div class="answer">$VardumpMe(getMyLevelOfProductsToShow)</div></li>
    <li><strong>Filter for segment:</strong> <div class="answer">$VardumpMe(FilterForGroupSegment)</div></li>
    <li><strong>Search Link</strong> <div class="answer">$VardumpMe(SearchResultLink)</div></li>
    <li><strong>Also Show Product Array:</strong> <div class="answer">$VardumpMe(getProductsToBeIncludedFromOtherGroupsArray)</div></li>
    <li><strong>ParentGroup:</strong> <div class="answer">$VardumpMe(ParentGroup)</div></li>
    <li><strong>Image:</strong> <div class="answer">$VardumpMe(Image)</div></li>
    <li><strong>Best Available Image (recursive):</strong> <div class="answer">$VardumpMe(BestAvailableImage)</div></li>
    <li><strong>Number of Direct Products:</strong> <div class="answer">$VardumpMe(getNumberOfProducts)</div></li>
    <li><strong>getSortFilterDisplayValues:</strong> <div class="answer">$VardumpMe(getSortFilterDisplayValues)</div></li>
    <li><strong>getBuyableClassName:</strong> <div class="answer">$VardumpMe(getBuyableClassName)</div></li>
    <li><strong>getProductsAlsoInOtherGroups:</strong> <div class="answer">$VardumpMe(getProductsAlsoInOtherGroups)</div></li>
    <li><strong>Child Categories (based on products):</strong> <div class="answer">$VardumpMe(ChildCategoriesBasedOnProducts)</div></li>
    <li><strong>Child Categories (based on hierarchy):</strong> <div class="answer">$VardumpMe(ChildCategories)</div></li>
    <li><strong>Show Levels:</strong> <div class="answer">$VardumpMe(getShowProductLevelsArray)</div></li>
</ul>
<% end_with %>

<% with $getRootGroupController %>

<% with $getProductGroupSchema %>
<hr /><hr /><h2 id="SchemaProvider" style="color: red; padding-top: 2rem">Schema Provider</h2><hr />
<ul>
    <li><strong>ClassName:</strong> <div class="answer">$VardumpClassName</div></li>
    <li><strong>Data:</strong> <div class="answer">$getData</div></li>
    <li><strong>Search Filter Options:</strong> <div class="answer">$getSearchFilterOptionsMap</div></li>
    <li><strong>Group Filter Options:</strong> <div class="answer">$getGroupFilterOptionsMap</div></li>
    <li><strong>Filter Options:</strong> <div class="answer">$getFilterOptionsMap</div></li>
    <li><strong>Sort Options:</strong> <div class="answer">$getSortOptionsMap</div></li>
    <li><strong>Display Options:</strong> <div class="answer">$getDisplayOptionsMap</div></li>
</ul>
<% end_with %>

<% with $getBaseProductList %>
<hr /><hr /><h2 id="BaseList" style="color: red; padding-top: 2rem">Base List</h2><hr />
<p>
Accessed by: <strong>ProductGroup.getBaseProductList()</strong>.
</p>
<ul>
    <li><strong>ClassName:</strong> <div class="answer">$VardumpClassName</div></li>
    <li><strong>Product (Buyable) Class Name:</strong> <div class="answer">$getBuyableClassName</div></li>
    <li><strong>Children to show (levels):</strong> <div class="answer">$getLevelOfProductsToShow</div></li>
    <li><strong>List of level options:</strong> <div class="answer">$getShowProductLevelsArray</div></li>
    <li><strong>Excluded Products 1:</strong> <div class="answer">$getExcludedProducts</div></li>
    <li><strong>Excluded Products 2:</strong> <div class="answer">$getBlockedProductIds</div></li>
    <li><strong>Product Count:</strong> <div class="answer">$getProductIds</div></li>
</ul>
<h3>Products</h3>
<ul>
    <li><strong>Product Ids:</strong> <div class="answer">$getProductIds</div></li>
    <li><strong>Products:</strong> <div class="answer">$getProducts</div></li>
    <li><strong>Raw Count:</strong> <div class="answer">$getRawCount</div></li>
    <li><strong>More than one:</strong> <div class="answer">$hasMoreThanOne</div></li>
    <li><strong>More than ten:</strong> <div class="answer">$hasMoreThanOne(10)</div></li>
    <li><strong>More than a million:</strong> <div class="answer">$hasMoreThanOne(99999999)</div></li>
    <li><strong>Direct Products:</strong> <div class="answer">$getDirectProducts</div></li>
    <li><strong>Direct Products without AlsoShow:</strong> <div class="answer">$getDirectProductsExclusingAlsoShow</div></li>
    <li><strong>Direct Products with AlsoShow:</strong> <div class="answer">$getDirectProductsWithAlsoShow</div></li>
    <li><strong>Direct Products AlsoShow only:</strong> <div class="answer">$getAlsoShowProductsFromRootGroupExclusive</div></li>
    <li><strong>Hierarchy Children:</strong> <div class="answer">$getChildProductsInclusive</div></li>
    <li><strong>Hierarchy Children without AlsoShow:</strong> <div class="answer">$getChildProductsExclusive</div></li>
    <li><strong>AlsoShow IDs:</strong> <div class="answer">$getAlsoShowProductsIds</div></li>
    <li><strong>AlsoShow Products:</strong> <div class="answer">$getAlsoShowProducts</div></li>
    <li><strong>AlsoShow Products without hierary ones:</strong> <div class="answer">$getAlsoShowProductsExclusive</div></li>
</ul>

<h3>Categories</h3>
<ul>
    <li><strong>Filter for candidates IDs:</strong> <div class="answer">$getFilterForCandidateCategoryIds</div></li>
    <li><strong>Filter for candidates:</strong> <div class="answer">$getFilterForCandidateCategories</div></li>
    <li><strong>Category Group IDs based on actual products:</strong> <div class="answer">$getParentGroupIdsBasedOnProducts</div></li>
    <li><strong>Categories based on actual products:</strong> <div class="answer">$getParentGroupsBasedOnProducts</div></li>
    <li><strong>Categories based on actual products, excluding root group:</strong> <div class="answer">$getParentGroupsBasedOnProductsExcludingRootGroup</div></li>

    <li><strong>Direct Child Categories:</strong> <div class="answer">$getDirectParentGroupsInclusive</div></li>
    <li><strong>Direct Child Categories without AlsoShow:</strong> <div class="answer">$getDirectParentGroupsExclusive</div></li>

    <li><strong>Hierarchy Category Ids:</strong> <div class="answer">$getParentGroupIds</div></li>
    <li><strong>Hierarchy Category:</strong> <div class="answer">$getParentGroups</div></li>
    <li><strong>Hierarchy Category without also show Categories:</strong> <div class="answer">$getParentGroupsExclusive</div></li>

    <li><strong>AlsoShow Category Ids related through AlsoShow (i.e. from all the products, what Categories are related through many-many):</strong> <div class="answer">$getAlsoShowParentIds</div></li>
    <li><strong>AlsoShow Categories related through AlsoShow (i.e. from all the products, what Categories are related through many-many):</strong> <div class="answer">$getAlsoShowParents</div></li>

    <li><strong>AlsoShow Product Parents (ie. from the Also Show products, what are the natural parents?) with hierarchy Categories:</strong> <div class="answer">$getAlsoShowProductsProductGroupInclusive</div></li>
    <li><strong>AlsoShow Product Parents (ie. from the Also Show products, what are the natural parents?) without hierarchy Categories:</strong> <div class="answer">$getAlsoShowProductsProductGroupsExclusive</div></li>
</ul>
<% end_with %>

<% with $getFinalProductList %>
<hr /><hr /><h2 id="FinalList" style="color: red; padding-top: 2rem">Final List</h2><hr />
<ul>
    <li><strong>ClassName:</strong> <div class="answer">$ClassName</div></li>
</ul>

<% end_with %>

<% with $getUserPreferencesClass %>
<hr /><hr /><h2 id="UserDetails" style="color: red; padding-top: 2rem">User Specific Details</h2><hr />
<ul>
    <li><strong>ClassName:</strong> <div class="answer">$VardumpClassName</div></li>
    <li><strong>use session to remember settings:</strong> <div class="answer">$getUseSessionAll</div></li>
    <li><strong>use session to remember settings for each page:</strong> <div class="answer">$getUseSessionPerPageAll</div></li>
    <li><strong>User Settings:</strong> <div class="answer">$getCurrentUserPreferences</div></li>
    <li><strong>Search Filter:</strong> <div class="answer">$getSearchFilterTitle</div></li>
    <li><strong>Group Filter:</strong> <div class="answer">$getGroupFilterTitle</div></li>
    <li><strong>Filter:</strong> <div class="answer">$getFilterTitle</div></li>
    <li><strong>Sort:</strong> <div class="answer">$getSortTitle</div></li>
    <li><strong>Display:</strong> <div class="answer">$getDisplayTitle</div></li>
</ul>
<% end_with %>

<hr /><hr /><h2 id="FinalList" style="color: red; padding-top: 2rem">Search</h2><hr />
<ul>
<li><strong>Debug:</strong> <div class="answer">$DebugSearchString</div></li>
</ul>

<% end_with %>
