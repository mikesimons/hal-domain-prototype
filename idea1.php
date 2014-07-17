<?php

require_once 'vendor/autoload.php';
require_once 'model.php';

function zoom($rel, $zoom) {
	foreach($zoom as $zrel) {
		if($zrel[0] == $rel) {
			return true;
		}
	}
	return false;
}

function shift_zoom($zoom) {
	$output = [];
	foreach($zoom as $z) {
		$tname = array_shift($z);

		if(count($z) > 0) {
			$output[] = $z;
		}
	}
	return $output;
}

function parse_zoom($zoom) {
	$zoom = explode(',', $zoom);
	$new_zoom = [];
	foreach($zoom as $z) {
		$z = "toplevel.$z";
		$new_zoom[] = explode('.', $z);
	}
	return $new_zoom;
}

function service($name, $val = null) {
	static $reg = [];
	if($val) {
		$reg[$name] = $val;
	}

	if(is_callable($reg[$name])) {
		return $reg[$name];
	} else {
		return $reg[$name];
	}
}

function opt($name, $opts) {
	return (array_key_exists($name, $opts) && $opts[$name]);
}

function mapper_for($data) {
	$class = is_object($data) ? get_class($data) : $data;
	return service("mapper." . $class);
}

function pretty_json($json) {
	return json_encode(json_decode($json), JSON_PRETTY_PRINT);
}

class ProductImageMapperConfig {
	public function url($vars) {
		return "/products/:sku/images/:image_id";
	}

	public function vars($vars, $object) {
		$vars['image_id'] = $object->id;
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [ [ 'name' => 'product', 'class' => 'Product', 'data' => $object->product() ] ];
	}

	public function children($object) {
		return null;
	}
}

class ProductImageCollectionMapperConfig {
	public function url($vars) {
		return "/products/:sku/images/";
	}

	public function vars($vars, $object) {
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return [ 'name' => 'images', 'class' => 'ProductImage', 'data' => $object ];
	}
}


class ProductMapperConfig {
	public function url($vars) {
		return "/products/:sku";
	}

	public function vars($vars, $object) {
		$vars['sku'] = $object->sku;
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [
			[ 'name' => 'images', 'class' => 'ProductImageCollection', 'data' => $object->images() ],
			[ 'name' => 'care_instructions', 'class' => 'CareInstruction', 'data' => $object->care_instruction() ],
			[ 'name' => 'attributes', 'class' => 'AttributeCollection', 'data' => $object->attributes() ]
		];
	}

	public function children() {
		return null;
	}
}

class ProductCollectionMapperConfig {
	public function url($vars) {
		return "/products/";
	}

	public function vars($vars, $object) {
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return [ 'name' => 'products', 'class' => 'Product', 'data' => $object ];
	}
}

class CareInstructionMapperConfig {
	public function url($vars) {
		return '/care_instructions/:care_instruction_id';
	}

	public function vars($vars, $object) {
		$vars['care_instruction_id'] = $object->id;
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return null;
	}
}

class CareInstructionCollectionMapperConfig {
	public function url($vars) {
		return "/care_instructions/";
	}

	public function vars($vars, $object) {
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return [ 'name' => 'care_instructions', 'class' => 'CareInstruction', 'data' => $object ];
	}
}

class AttributeMapperConfig {
	public function url($vars) {
		return isset($vars['sku']) ? '/product/:sku/attributes/:attribute_slug' : '/attributes/:attribute_slug';
	}

	public function vars($vars, $object) {
		$vars['attribute_slug'] = $object->slug;
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return null;
	}
}

class AttributeCollectionMapperConfig {
	public function url($vars) {
		return isset($vars['sku']) ? '/product/:sku/attributes' : '/attributes/';
	}

	public function vars($vars, $object) {
		return $vars;
	}

	public function attrs($object) {
		return $object->getAttrs();
	}

	public function rels($object) {
		return [];
	}

	public function children($object) {
		return [ 'name' => 'attributes', 'class' => 'Attribute', 'data' => $object ];
	}
}

class GenericMapper {

	public function __construct($config) {
		$this->config = $config;
	}

	public function map($object, $vars, $zoom) {
		$vars = $this->config->vars($vars, $object);
		$hal = new Nocarrier\Hal($this->url($vars), $this->config->attrs($object));

		foreach($this->config->rels($object) as $rel) {	
			if($rel['data'] == null) {
				continue;
			}
			$mapper = mapper_for($rel['class']);
			$mapper->linkOrEmbed($rel['name'], $rel['data'], $hal, $vars, shift_zoom($zoom));
		}

		$children = $this->config->children($object);
		if($children) {
			$mapper = mapper_for($children['class']);
			foreach($children['data'] as $child) {
				if($children['data'] == null) {
					continue;
				}
				$mapper->linkOrEmbed($children['name'], $child, $hal, $vars, $zoom, [ 'force_embed' => true ]);
			}
		}

		return $hal;
	}

	public function linkOrEmbed($name, $object, $hal, $vars, $zoom, $opts = []) {
		if(zoom($name, $zoom) || opt('force_embed', $opts)) {
			$hal->addResource($name, $this->map($object, $vars, $zoom));
		} else {
			$vars = $this->config->vars($vars, $object);
			$hal->addLink($name, $this->url($vars));
		}
	}

	private function url($vars) {
		$keys = array_map(function($key) { return ":$key"; }, array_keys($vars));
		return str_replace($keys, $vars, $this->config->url($vars));
	}
}

service('elasticsearch', new Elasticsearch\Client(['hosts' => [ '127.0.0.1:9200' ]]));
service('repo.product', new ProductRepository(service('elasticsearch')));
service('repo.product_images', new ProductImageRepository(service('elasticsearch')));
service('repo.care_instructions', new CareInstructionRepository(service('elasticsearch')));
service('repo.attributes', new AttributeRepository(service('elasticsearch')));

service('mapper.Product', new GenericMapper(new ProductMapperConfig));
service('mapper.ProductCollection', new GenericMapper(new ProductCollectionMapperConfig));
service('mapper.ProductImage', new GenericMapper(new ProductImageMapperConfig));
service('mapper.ProductImageCollection', new GenericMapper(new ProductImageCollectionMapperConfig));
service('mapper.CareInstruction', new GenericMapper(new CareInstructionMapperConfig));
service('mapper.CareInstructionCollection', new GenericMapper(new CareInstructionCollectionMapperConfig));
service('mapper.Attribute', new GenericMapper(new AttributeMapperConfig));
service('mapper.AttributeCollection', new GenericMapper(new AttributeCollectionMapperConfig));
