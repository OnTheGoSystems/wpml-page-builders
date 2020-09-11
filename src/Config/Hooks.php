<?php

namespace WPML\PB\Config;

use function WPML\FP\tap as tap;

class Hooks implements \IWPML_Action {

	/** @var Parser $parser */
	private $parser;

	/** @var Storage $storage */
	private $storage;

	public function __construct(
		Parser $parser,
		Storage $storage
	) {
		$this->parser  = $parser;
		$this->storage = $storage;
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', tap( [ $this, 'extractConfig' ] ) );
	}

	public function extractConfig( array $allConfig ) {
		$this->storage->update( $this->parser->extract( $allConfig ) );
	}
}
