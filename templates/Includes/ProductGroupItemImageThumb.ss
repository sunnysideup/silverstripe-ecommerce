<div class="productImage">
<% if Image %>
	<a href="$Link"><img src="$Image.Thumbnail.URL" alt="<% sprintf(_t("Product.IMAGE","%s image"),$Title) %>" width="$Image.Thumbnail.Width" height="$Image.Thumbnail.Height" /></a>
<% else %>
	<a href="$Link" class="noImage"><img src="$EcomConfig.DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="$DummyImage.ThumbWidth" height="$DummyImage.ThumbHeight"></a>
<% end_if %>
</div>


