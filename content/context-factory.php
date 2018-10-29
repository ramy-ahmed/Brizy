<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 8/17/18
 * Time: 3:18 PM
 */

class Brizy_Content_ContextFactory {

	/**
	 * @param $project
	 * @param $brizy_post
	 * @param $wp_post
	 * @param $contentHtml
	 *
	 * @return Brizy_Content_Context
	 */
	static public function createContext( $project, $wp_post ) {
		$context = new Brizy_Content_Context( $project, $wp_post );

		if ( $wp_post ) {
			$context->setAuthor( $wp_post->post_author );
		}

		/**
		 * We send here the $wp_post for compatibility
		 */
		return apply_filters( 'brizy_context_create', $context, $wp_post );
	}
}