<li class="productItem $FirstLast item$Pos<% if FeaturedProduct %> featured<% end_if %>">
    <% include Sunnysideup\Ecommerce\Includes\ProductGroupItemImageThumb %>
    <h3 class="productTitle"><a href="$Link">$Title</a></h3>
    <div class="limtedContentHolder">$Content.Summary</div>
    <p class="moreInformation"><a href="$Link"><% _t("Product.MOREINFO","More info ...") %></a></p>
    <% include Sunnysideup\Ecommerce\Includes\ProductActionsForGroup %>
</li>
