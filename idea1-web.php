<?php

require_once 'vendor/autoload.php';
require_once 'idea1.php';

use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application(); 
$app['debug'] = true;

function common($request) {
	$vars = $request->query->all();
	// merge attributes
	$zoom = "";
	if(isset($vars['zoom'])) {
		$zoom = parse_zoom($vars['zoom']);
		unset($vars['zoom']);
	}
	return [ $vars, $zoom ];
}

function boilerplate_hal($f) {
	return function(Request $request) use ($f) {
		list($vars, $zoom) = common($request);	
		$data = $f($request);
		$hal = mapper_for($data)->map($data, $vars, $zoom);
		return $hal->asJson();
	};	
}

$app->get('/products', boilerplate_hal( function($request) {
	return service('repo.product')->findAll();
}));

$app->get('/products/{sku}', boilerplate_hal( function($request) {
	return service('repo.product')->findBySku($request->attributes->get('sku'));
}));

$app->get('/products/{sku}/images', boilerplate_hal( function($request) {
	return service('repo.product')->findBySku($request->attributes->get('sku'))->images();
}));

$app->get('/products/{sku}/images/{id}', boilerplate_hal( function($request) {
	return service('repo.product_image')->findById($request->attributes->get('id'));
}));

$app->get('/products/{sku}/care', boilerplate_hal( function($request) {
	return service('repo.product')->findBySku($request->attributes->get('sku'))->care_instruction();
}));

$app->get('/products/{sku}/attributes/', boilerplate_hal( function($request) {
	return service('repo.product')->findBySku($request->attributes->get('sku'))->attributes();
}));

$app->get('/products/{sku}/attributes/{slug}', boilerplate_hal( function($request) {
	return service('repo.attribute')->findBySlug($request->attributes->get('slug'));
}));

$app->get('/care_instructions', boilerplate_hal( function($request) {
	return service('repo.care_instruction')->findAll();
}));

$app->get('/care_instructions/{id}', boilerplate_hal( function($request) {
	return service('repo.care_instruction')->findById($request->attributes->get('id'));
}));

$app->get('/attributes', boilerplate_hal( function($request) {
	return service('repo.attribute')->findAll();
}));

$app->get('/attributes/{slug}', boilerplate_hal( function($request) {
	return service('repo.attribute')->findBySlug($request->attributes->get('slug'));
}));

$app->run();
