<?php

require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Nocarrier\Hal;

$hal = new Hal('/products?zoom=product.images,attributes');
$hal->addLink('next', '/products?zoom=product.images,attributes&page=2');

$attribute1 = new Hal(
    '/attribute/water-repellant',
    array(
        'like-a-duck' => true
    )
);

$attribute2 = new Hal(
    '/attribute/essence-of-chuck-norris',
    array(
        'invicible' => true
    )
);

$product1image1 = new Hal(
    '/products/MSP001/images/1',
    array( 'href' => 'http://example.org/images/MSP001.png' )
);

$product1 = new Hal(
    '/products/MSP001',
    array(
        'sku' => 'MSP001',
        'name' => 'Spangleh stuff',
        'description' => 'Some super spangleh stuff'
    )
);

$product2 = new Hal(
    '/products/MSP002',
    array(
        'sku' => 'MSP002',
        'name' => 'Spangleh tings',
        'description' => 'Some super spangleh tings'
    )
);

$product1image1->addLink('product', '/products/MSP001');

$product1->addResource('images', $product1image1);
$product1->addResource('attributes', $attribute1);
$product1->addResource('attributes', $attribute2);
//$product1->addLink('care_instructions', '/products/MSP001/care'); // ProductCareInstructions
$product1->addLink('care_instructions', '/care/wood'); // CareInstructions

$product2->addResource('attributes', $attribute2);


$hal->addResource('products', $product1);
$hal->addResource('products', $product2);

echo $hal->asJson();
