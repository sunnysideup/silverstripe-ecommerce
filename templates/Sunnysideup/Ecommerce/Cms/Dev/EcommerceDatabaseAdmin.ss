<style type="text/css">
    #EcommerceDatabaseAdmin {margin: 20px;}
    #EcommerceDatabaseAdmin h3 {margin-top: 40px; margin-bottom: 0px; padding-bottom: 0px; border-bottom: 1px solid #002137; }
    #EcommerceDatabaseAdmin li {margin-top: 10px; margin-bottm: 10px; list-style: lower-greek; margin-left: 10px;}
    #EcommerceDatabaseAdmin a:hover {background-color: yellow;}
</style>
<div id="EcommerceDatabaseAdmin">
    <div id="Outcome">
        $Outcome
    </div>
    <% if OverallConfig %>
    <h3>1. Check your settings and start coding</h3>
    <p>
        Check your settings whenever you are working on this site to make sure they are all up-to-date and valid.
        There are also a bunch of links here to useful places for developing e-commerce code.
    </p>
    <ul>
    <% loop OverallConfig %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if EcommerceSetup %>
    <h3>2. E-commerce Setup</h3>
    <p>These actions are generally only run once to setup e-commerce.</p>
    <ul>
    <% loop EcommerceSetup %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if DataReview %>
    <h3>3. Review Data</h3>
    <p>Here are some quick links to review things like sales data, product searches, etc....</p>
    <ul>
    <% loop DataReview %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if RegularMaintenance %>
    <h3>4. Regular Maintenance</h3>
    <p>These actions should be run as cron job or you should configure e-commerce in such a way that they run regularly.</p>
    <ul>
    <% loop RegularMaintenance %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if DebugActions %>
    <h3>5. Building and Debugging</h3>
    <p>Use the options listed below to debug your e-commerce application.</p>
    <ul>
    <% loop DebugActions %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>


    <% if DataCleanups %>
    <h3>6. Data Cleaning</h3>
    <ul>
    <% loop DataCleanups %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>


    <% if Migrations %>
    <h3>7. Migration</h3>
    <p>Use the options listed below whenever your upgrade your source code.</p>
    <ul>
    <% loop Migrations %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if CrazyShit %>
    <h3>8. You are MAD?</h3>
    <p>Cleaning the slate ...</p>
    <ul>
    <% loop CrazyShit %>
        <li><a href="$Link">$Title</a>: $Description</li>
    <% end_loop %>
    </ul>
    <% end_if %>

    <% if Tests %>
    <h3>9. Ecommerce Unit Tests</h3>
    <ul>
        <li><a href="/dev/tests/$AllTests"><strong>Run all ecommerce unit tests</strong></a></li>
    <% loop Tests %>
        <li><a href="/dev/tests/$Class">$Name</a></li>
    <% end_loop %>
    </ul>
    <% end_if %>

</div>
