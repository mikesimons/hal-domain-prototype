<?php

require_once 'idea1.php';

$args = isset($argv[1]) ? $argv[1] : "";
$vars = [];
$zoom = [];
parse_str($args, $vars);
if(array_key_exists('zoom', $vars)) {
	$zoom = parse_zoom($vars['zoom']);
	unset($vars['zoom']);
}

$data = service('repo.product')->findAll();
$hal = mapper_for($data)->map($data, $vars, $zoom);
echo json_encode(json_decode($hal->asJson()), JSON_PRETTY_PRINT);
