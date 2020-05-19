<?php

namespace WPML\PB\AutoUpdate;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\invoke;
use function WPML\FP\pipe;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const HASH_SEP = '-';

	/** @var \WPML_PB_Integration $pbIntegration */
	private $pbIntegration;

	/** @var \WPML_Translation_Element_Factory $elementFactory */
	private $elementFactory;

	public function __construct(
		\WPML_PB_Integration $pbIntegration,
		\WPML_Translation_Element_Factory $elementFactory
	) {
		$this->pbIntegration     = $pbIntegration;
		$this->elementFactory    = $elementFactory;
	}

	public function add_hooks() {
		add_filter( 'wpml_tm_post_md5_content', [ $this, 'getMd5ContentFromPackageStrings' ], 10, 2 );
		add_action( 'wpml_after_save_post', [ $this, 'resaveTranslationsAfterSavePost' ], 10, 4 );
	}

	/**
	 * @param string   $content
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public function getMd5ContentFromPackageStrings( $content, $post ) {
		// $joinPackageStringHashes :: \WPML_Package → string
		$joinPackageStringHashes = pipe(
			Obj::propOr( [], 'string_data' ),
			Obj::keys(),
			Lst::sort( Relation::gt() ),
			Lst::join( self::HASH_SEP )
		);

		return Maybe::of( $post->ID )
			->map( [ self::class, 'getPackages' ] )
			->map( Fns::map( $joinPackageStringHashes ) )
			->filter()
			->map( Lst::join( self::HASH_SEP ) )
			->getOrElse( $content );
	}

	/**
	 * @param int $postId
	 *
	 * @return \WPML_Package[]
	 */
	public static function getPackages( $postId ) {
		return apply_filters( 'wpml_st_get_post_string_packages', [], $postId );
	}

	/**
	 * @param int         $postId
	 * @param int         $trid
	 * @param string      $lang
	 * @param string|null $sourceLang
	 */
	public function resaveTranslationsAfterSavePost( $postId, $trid, $lang, $sourceLang ) {
		if ( $sourceLang || ! self::getPackages( $postId ) ) {
			return;
		}

		// $ifOriginal :: \WPML_Post_Element → bool
		$ifOriginal = pipe( invoke( 'get_source_language_code' ), Logic::not() );

		// $ifCompleted :: \WPML_Post_Element → bool
		$ifCompleted = pipe( [ TranslationStatus::class, 'get' ], Relation::equals( ICL_TM_COMPLETE ) );

		// $resaveElement :: \WPML_Post_Element → null
		$resaveElement = [ $this->pbIntegration, 'resave_post_translation_in_shutdown' ];

		wpml_collect( $this->elementFactory->create_post( $postId )->get_translations() )
			->reject( $ifOriginal )
			->filter( $ifCompleted )
			->each( $resaveElement );
	}
}
