<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<% base_tag %>
	$MetaTags
	<link rel="shortcut icon" href="/favicon.ico" />
	<script type="text/javascript" src="/framework/thirdparty/jquery/jquery.js"></script>
	<style type="text/css">
		html, body {
			width: 100%;
			height: 100%;
			margin: 0 auto;
			background-color: #ffffff;
		}
		body {
			text-align: center;
		}
		#Outer {
			text-align: center;
			position: relative;
			top: 50%;
			left: 50%;
		}
		#Inner {
			position: absolute;
			margin-top: -200px;
			margin-left: -200px;
			height: 400px;
			width: 400px;
			overflow: auto;
		}
		#Inner img {
			display: block;
			margin: 10px 0;
			border: 0;
		}
	</style>
</head>
<body>
	<div id="Outer">
		<div id="Inner">
			<div id="PaymentLogoImage">$Logo</div>
			<div id="PaymentLoadingImage"><img src="ecommerce/images/loading.gif" alt="Loading image"></div>
			<div id="PaymentFormHolder">$Form</div>
		</div>
	</div>
</body>
 </html>
