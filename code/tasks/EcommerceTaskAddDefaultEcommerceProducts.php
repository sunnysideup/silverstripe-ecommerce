<?php


/**
 * @description: see AddDefaultEcommerceProducts::$description
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 * @inspiration: Silverstripe Ltd, Jeremy
 **/

class AddDefaultEcommerceProducts extends BuildTask {


	protected $title = "Add default Products";

	protected $description = "Adds two default Products and a Product Group (Category) to your site.";

	function run($request) {

		if(Product::get()->Count() == 0) {
			if(ProductGroup::get()->Count() == 0) {
				$productGroup1 = new ProductGroup();
				$productGroup1->Title = 'Products';
				$productGroup1->Content = "
					<p>This is the top level products page, it uses the <em>product group</em> page type, and it allows you to show your products checked as 'featured' on it. It also allows you to nest <em>product group</em> pages inside it.</p>
					<p>For example, you have a product group called 'DVDs', and inside you have more product groups like 'sci-fi', 'horrors' or 'action'.</p>
					<p>In this example we have setup a main product group (this page), with a nested product group containing 2 example products.</p>
				";
				$productGroup1->URLSegment = 'products';
				$productGroup1->writeToStage('Stage');
				$productGroup1->publish('Stage', 'Live');
				DB::alteration_message('Product group page \'Products\' created', 'created');

			}
			$content = '<p>This is a <em>product</em>. It\'s description goes into the Content field as a standard SilverStripe page would have it\'s content. This is an ideal place to describe your product.</p>';

			$page1 = new Product();
			$page1->Title = 'Example product';
			$page1->Content = $content . '<p>You may also notice that we have checked it as a featured product and it will be displayed on the main Products page.</p>';
			$page1->URLSegment = 'example-product';
			$page1->ParentID = $productGroup1->ID;
			$page1->Price = '15.00';
			$page1->FeaturedProduct = true;
			$page1->writeToStage('Stage');
			$page1->publish('Stage', 'Live');
			DB::alteration_message('Product page \'Example product\' created', 'created');

			$page2 = new Product();
			$page2->Title = 'Example product 2';
			$page2->Content = $content;
			$page2->URLSegment = 'example-product-2';
			$page2->ParentID = $productGroup1->ID;
			$page2->Price = '25.00';
			$page2->writeToStage('Stage');
			$page2->publish('Stage', 'Live');
			DB::alteration_message('Product page \'Example product 2\' created', 'created');
		}
		else {
			DB::alteration_message('No products created as they already exist.');
		}

	}




}

