<?php

require 'vendor/autoload.php';


trait SimpleDomainObject {
	private $data;

	public function __construct($data) {
		$this->data = $data;
	}

	public function __get($var) {
		if(!array_key_exists($var, $this->data)) {
			throw new Exception("Invalid struct member $var");
		}
		$data = $this->data[$var];
		return is_object($data) ? clone $data : $data;
	}

	public function has($var) {
		return array_key_exists($var, $this->data);
	}

	public function getAttrs() {
		return $this->data;
	}
}

trait SimpleCollection {
	private $data;
	public function __construct($data, $attrs = []) {
		$this->data = $data;
		$this->attrs = $attrs;
	}

	public function current() {
		return current($this->data);
	}

	public function key() {
		return key($this->data);
	}

	public function next() {
		return next($this->data);
	}

	public function rewind() {
		return reset($this->data);
	}

	public function valid() {
		return isset($this->data[$this->key()]);
	}

	public function getAttrs() {
		return $this->attrs;
	}
}

trait SimpleEsSearch {
	private function findBy($field, $value, $override_params = []) {
		return $this->simpleEsQuery([ 'match' => [ $field => $value ] ], $override_params);
	}

	public function findAll() {
		return $this->asCollection($this->simpleEsQuery([ 'match_all' => [] ], []));
	}

	private function simpleEsQuery($query, $override_params = [], $opts = []) {
		if(isset($opts['body']) && !$opts['body']) {
			$params = [ 'query' => $query ];
		} else {
			$params = [ 'body' => [ 'query' => $query ] ];
		}
		return $this->es->search(array_merge($this->params, $params, $override_params));
	}

	private function asCollection($result) {
		$result = $result['hits']['hits'];
		$domain_objects = [];
		foreach($result as $r) {
			$domain_objects[] = new $this->domain_class($r['_source']);
		}
		return new $this->domain_collection_class($domain_objects);
	}

	private function asSingle($result) {
		$result = array_shift($result['hits']['hits']);
		if(!$result) {
			return null;
		}
		return new $this->domain_class($result['_source']);
	}
}

class Product {
	use SimpleDomainObject;
	public function images() {
		return service('repo.product_images')->findAllBySku($this->sku);
	}

	public function care_instruction() {
		if(!$this->has('care_instruction_id')) return null;
		return service('repo.care_instructions')->findById($this->care_instruction_id);
	}

	public function attributes() {
		if(!$this->has('attribute_ids')) {
			return null;
		}
		return service('repo.attributes')->findAllByIds($this->attribute_ids);
	}
}

class ProductCollection implements Iterator {
	use SimpleCollection;
}

class ProductRepository {
	use SimpleEsSearch;

        public function __construct($es) {
		$this->es = $es;
		$this->domain_class = 'Product';
		$this->domain_collection_class = 'ProductCollection';
		$this->params = [ 'index' => 'products', 'type' => 'product'];
	}

	public function findBySku($sku) {
		return $this->asSingle(
			$this->findBy('sku', $sku)
		);
	}
}

class Attribute { 
	use SimpleDomainObject;
}

class AttributeCollection implements Iterator {
	use SimpleCollection;
}
 
class AttributeRepository {
	use SimpleEsSearch;

        public function __construct($es) {
		$this->es = $es;
		$this->domain_class = 'Attribute';
		$this->domain_collection_class = 'AttributeCollection';
		$this->params = [ 'index' => 'attributes', 'type' => 'attribute'];
	}

	public function findAllByIds($ids) {
		$q = [ 'index' => 'attributes', 'type' => 'attribute', 'q' => [ 'constant_score' => [ 'filter' => [ 'terms' => [ 'id' => $ids ] ] ] ] ];
		return $this->asCollection(
			$this->es->search($q)
		);
	}
}

class ProductImage {
	use SimpleDomainObject;
	public function product() {
		return service('repo.product')->findBySku($this->sku);
	}
}

class ProductImageCollection implements Iterator {
	use SimpleCollection;
}

class ProductImageRepository {
	use SimpleEsSearch;

        public function __construct($es) {
		$this->es = $es;
		$this->domain_class = 'ProductImage';
		$this->domain_collection_class = 'ProductImageCollection';
		$this->params = [ 'index' => 'product_images', 'type' => 'product_image'];
	}

	public function findAllBySku($sku) {
		return $this->asCollection(
			$this->findBy('sku', $sku)
		);
	}
}

class CareInstruction {
	use SimpleDomainObject;
}

class CareInstructionCollection implements Iterator{
	use SimpleCollection;
}

class CareInstructionRepository {
	use SimpleEsSearch;

        public function __construct($es) {
		$this->es = $es;
		$this->domain_class = 'CareInstruction';
		$this->domain_collection_class = 'CareInstructionCollection';
		$this->params = [ 'index' => 'care_instructions', 'type' => 'care_instruction'];
	}

	public function findById($id) {
		return $this->asSingle($this->findBy('id', $id));
	}
}
