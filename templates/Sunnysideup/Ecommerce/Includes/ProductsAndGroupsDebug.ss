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
    <li><strong>Group Filter Options:</strong> $getOptions(GROUPFILTER)</li>
    <li><strong>Filter Options:</strong> $getOptions(FILTER)</li>
    <li><strong>Sort Options:</strong> $getOptions(SORT)</li>
    <li><strong>Display Options:</strong> $getOptions(DISPLAY)</li>
</ul>
<% end_with %>

<h2>Base List</h2>
<ul>
    <li><strong>ClassName:</strong> $ClassName</li>
</ul>

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
