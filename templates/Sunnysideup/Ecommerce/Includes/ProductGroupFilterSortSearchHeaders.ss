<ul class="filterSortSearchHeaders">

<% if HasSearchFilters %>
    <li>
        <a href="#SearchFilterForList" class="openCloseSectionLink close">$SearchFilterHeader<% if $CurrentFilterTitle %> ($CurrentSearchFilterTitle)<% end_if %></a>
    </li>
<% end_if %>

<% if HasGroupFilters %>
    <li>
        <a href="#GroupFilterForList" class="openCloseSectionLink close">$GroupFilterHeader<% if $CurrentGroupFilterTitle %> ($CurrentGroupFilterTitle)<% end_if %></a>
    </li>
<% end_if %>

<% if HasFilters %>
    <li>
    <a href="#FilterForList" class="openCloseSectionLink close">$FilterHeader<% if $CurrentFilterTitle %> ($CurrentFilterTitle)<% end_if %></a>
    </li>
<% end_if %>

<% if HasSorts %>
    <li>
        <a href="#SortForList" class="openCloseSectionLink close">$SortHeader<% if CurrentFilterTitle %> ($CurrentSortTitle)<% end_if %></a>
    </li>
<% end_if %>

<% if DisplayLinks %>
    <li>
        <a href="#DisplayForList" class="openCloseSectionLink close">$DisplayHeader<% if $CurrentDisplayTitle %> ($CurrentDisplayTitle)<% end_if %></a>
    </li>
<% end_if %>
</ul>
