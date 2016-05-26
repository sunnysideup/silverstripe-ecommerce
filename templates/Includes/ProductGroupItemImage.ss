<div class="productImage">
<% if Image %>
	<a href="$Link"><img src="$Image.SmallImage.URL" alt="<%t Product.IMAGE '{name} image' name=$Title.ATT %>" width="$Image.SmallImage.Width" height="$Image.SmallImage.Height" /></a>
<% else %>
	<a href="$Link" class="noImage"><img src="$EcomConfig.DefaultImageLink" alt="<% _t("Product.NOIMAGEAVAILABLE","no image available") %>" width="$DummyImage.SmallWidth" height="$DummyImage.SmallHeight" /></a>
<% end_if %>
</div>


