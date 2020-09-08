<?php

namespace WPML\PB\AutoUpdate;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Relation;
use WPML\PB\Shutdown\Hooks as ShutdownHooks;
use function WPML\FP\invoke;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	const HASH_SEP = '-';

	/** @var \WPML_PB_Integration $pbIntegration */
	private $pbIntegration;

	/** @var \WPML_Translation_Element_Factory $elementFactory */
	private $elementFactory;

	/** @var array $translationStatusesUpdaters */
	private $translationStatusesUpdaters = [];

	public function __construct(
		\WPML_PB_Integration $pbIntegration,
		\WPML_Translation_Element_Factory $elementFactory
	) {
		$this->pbIntegration             = $pbIntegration;
		$this->elementFactory            = $elementFactory;
	}

	public function add_hooks() {
		if ( $this->isTmLoaded() ) {
			add_filter( 'wpml_pb_auto_update_enabled', '__return_true' );
			add_filter( 'wpml_tm_delegate_translation_statuses_update', [ $this, 'enqueueTranslationStatusUpdate'], 10, 3 );
			add_filter( 'wpml_tm_post_md5_content', [ $this, 'getMd5ContentFromPackageStrings' ], 10, 2 );
			add_action( 'shutdown', [ $this, 'afterRegisterAllStringsInShutdown' ], ShutdownHooks::PRIORITY_REGISTER_STRINGS + 1 );
		}
	}

	public function isTmLoaded() {
		return defined( 'WPML_TM_VERSION' );
	}

	/**
	 * @param $isDelegated
	 * @param $originalPostId
	 * @param $statusesUpdater
	 *
	 * @return bool
	 */
	public function enqueueTranslationStatusUpdate( $isDelegated, $originalPostId, $statusesUpdater ) {
		$this->translationStatusesUpdaters[ $originalPostId ] = $statusesUpdater;
		return true;
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
			invoke( 'get_package_strings' )->with( true ),
			Lst::pluck( 'value' ),
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
	 * We need to update translation statuses after string registration
	 * to make sure we build the content hash with the new strings.
	 */
	public function afterRegisterAllStringsInShutdown() {
		if ( $this->translationStatusesUpdaters ) {
			do_action( 'wpml_cache_clear' );

			foreach ( $this->translationStatusesUpdaters as $originalPostId => $translationStatusesUpdater ) {
				call_user_func( $translationStatusesUpdater );
				$this->resaveTranslations( $originalPostId );
			}
		}
	}

	/**
	 * @param int $postId
	 */
	private function resaveTranslations( $postId ) {
		if ( ! self::getPackages( $postId ) ) {
			return;
		}

		// $ifOriginal :: \WPML_Post_Element → bool
		$ifOriginal = pipe( invoke( 'get_source_language_code' ), Logic::not() );

		// $ifCompleted :: \WPML_Post_Element → bool
		$ifCompleted = pipe( [ TranslationStatus::class, 'get' ], Relation::equals( ICL_TM_COMPLETE ) );

		// $resaveElement :: \WPML_Post_Element → null
		$resaveElement = \WPML\FP\Fns::unary( partialRight( [ $this->pbIntegration, 'resave_post_translation_in_shutdown' ], false ) );

		wpml_collect( $this->elementFactory->create_post( $postId )->get_translations() )
			->reject( $ifOriginal )
			->filter( $ifCompleted )
			->each( $resaveElement );
	}
}
