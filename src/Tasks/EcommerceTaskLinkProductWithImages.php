<?php

namespace Sunnysideup\Ecommerce\Tasks;

use SilverStripe\Assets\File;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\Ecommerce\Pages\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Add any Image (or other file) to a product using the InternalItemID.
 *
 * @author: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 */
class EcommerceTaskLinkProductWithImages extends BuildTask
{
    /**
     * output messages?
     *
     * @var bool
     */
    public $verbose = true;

    protected string $title = 'Find product images';

    protected static string $description = 'Finds product images (or other files) based on their name. That is, any image name [InteralItemID]_[two digits].[png/gif/jpg/pdf/(etc)] will automatically be linked to the product. For example SKUAAFF_1 or SKU_02. All files ending in a number from 00 to 99 will be added (e.g. 02, 5 or 55). Also SKUAAFF.jpg (without the standard ending with underscore and number) will be added to the product where InternalItemID equals SKUAAFF.';

    protected static string $commandName = 'ecommerce-link-product-images';

    /**
     * In the default e-commerce, each product only has one image.
     * Many e-commerce sites, however, like to have more than one image per product.
     *
     * @var string
     */
    protected $productManyManyField = 'AdditionalImages';

    /**
     * Starting point for selecting products
     * Usually starts at zero and goes up to the total number of products.
     *
     * @var int
     */
    protected $start = 0;

    /**
     * The number of products selected per cycle.
     *
     * @var int
     */
    protected $limit = 100;

    protected $productID = 0;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->start = (int) $input->getOption('start');
        $this->productID = (int) $input->getOption('productid');
        $this->limit = (int) $input->getOption('limit') ?: $this->limit;

        if ($this->productManyManyField) {
            $products = Product::get()->limit($this->limit, $this->start);
            if ($this->productID) {
                $products = $products->filter(['ID' => $this->productID]);
            }

            if ($products->exists()) {
                foreach ($products as $product) {
                    if ($product->InternalItemID) {
                        if ($product->hasMethod($this->productManyManyField)) {
                            $whereStringArray = [];
                            $whereStringArray[] = $product->InternalItemID;
                            for ($i = 0; $i < 10; ++$i) {
                                for ($j = 0; $j < 10; ++$j) {
                                    $number = strval($i) . strval($j);
                                    $whereStringArray[] = $product->InternalItemID . '_' . $number;
                                }
                            }

                            $images = File::get()
                                ->filter(['Name:PartialMatch' => $whereStringArray])
                            ;
                            if ($images->exists()) {
                                $method = $this->productManyManyField;
                                $collection = $product->{$method}();
                                foreach ($images as $image) {
                                    $collection->add($image);
                                    if ($this->verbose) {
                                        $output->writeln('Adding image ' . $image->Name . ' to ' . $product->Title);
                                    }
                                }
                            } elseif ($this->verbose) {
                                $output->writeForHtml('No images where found for product with Title <i>' . $product->Title . '</i>: no images could be added.');
                            }
                        } elseif ($this->verbose) {
                            $output->writeForHtml('The method <i>' . $this->productManyManyField . '</i> does not exist on <i>' . $product->Title . ' (' . $product->ClassName . ')</i>: no images could be added.');
                        }
                    } elseif ($this->verbose) {
                        $output->writeForHtml('No InternalItemID set for <i>' . $product->Title . '</i>: no images could be added.');
                    }
                }

                $productCount = Product::get()->count();
                if ($this->start + $this->limit < $productCount) {
                    $output->writeln('Processed ' . ($this->start + $this->limit) . ' of ' . $productCount . ' products. Run again with --start=' . ($this->start + $this->limit) . ' to continue.');
                }
            }
        } elseif ($this->verbose) {
            $output->writeln('No product Many-2-Many method specified.  No further action taken.');
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('start', 's', InputOption::VALUE_OPTIONAL, 'Starting point for selecting products', 0),
            new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'The number of products selected per cycle', 100),
            new InputOption('productid', 'p', InputOption::VALUE_OPTIONAL, 'Specific product ID to process', 0),
        ];
    }

    public function setProductID($id)
    {
        $this->productID = $id;
    }
}
