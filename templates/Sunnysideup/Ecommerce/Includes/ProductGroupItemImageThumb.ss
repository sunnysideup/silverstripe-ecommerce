<div class="productImage cartImage">
<% if BestAvailableImage %>
	<a href="$Link"><img src="$BestAvailableImage.Thumbnail.URL" alt="<%t Product.IMAGE '{name} image' name=$Title.ATT %>" width="$BestAvailableImage.Thumbnail.Width" height="$BestAvailableImage.Thumbnail.Height" /></a>
<% else %>
	<a href="$Link" class="noImage"><img src="$EcomConfig.DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="$DummyImage.ThumbWidth" height="$DummyImage.ThumbHeight" /></a>
<% end_if %>
</div>
