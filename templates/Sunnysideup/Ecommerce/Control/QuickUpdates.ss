<% include Sunnysideup\Ecommerce\Control\Includes\QuickUpdatesHeader %>
<h1>Quick Updates Available</h1>
<% if $Menu %>
<ul>
    <% loop Menu %>
    <li><a href="$Link">$Title</a></li>
    <% end_loop %>
</ul>
<% else %>
    <p class="message warning">
        Sorry, there are no quick-updates available.
    </p>
<% end_if %>
<% include Sunnysideup\Ecommerce\Control\Includes\QuickUpdatesFooter %>
