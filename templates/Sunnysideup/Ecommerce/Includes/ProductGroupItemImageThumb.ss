<div class="productImage cartImage">
<% if $BestAvailableImage %>
    <a href="$Link"><img src="$BestAvailableImage.URL" alt="$Title.ATT" width="$100" height="$BestAvailableImage.Height" /></a>
<% else %>
    <a href="$Link" class="noImage"><img src="$EcomConfig.DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="100" /></a>
<% end_if %>
</div>
