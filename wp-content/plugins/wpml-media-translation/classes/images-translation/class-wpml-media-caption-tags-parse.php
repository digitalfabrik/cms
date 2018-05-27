<?php

/**
 * Class WPML_Media_Caption_Tags_Parse
 */
class WPML_Media_Caption_Tags_Parse {

	/**
	 * @var WPML_Media_Img_Parse
	 */
	private $img_parser;

	/**
	 * WPML_Media_Caption_Tags_Parse constructor.
	 *
	 * @param WPML_Media_Img_Parse $img_parser
	 */
	public function __construct( WPML_Media_Img_Parse $img_parser ) {
		$this->img_parser = $img_parser;
	}

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	public function get_tags( $text ) {
		$caption_tags = array();

		if ( preg_match_all( '/\[caption (.+)\](.+)\[\/caption\]/s', $text, $matches ) ) {

			foreach ( $matches[1] as $i => $match ) {
				$caption_tags[ $i ]['attributes']    = $this->get_attributes_array( $match );
				$caption_tags[ $i ]['attachment_id'] = $this->get_attachment_id( $caption_tags[ $i ]['attributes'] );
			}

			foreach ( $matches[2] as $i => $match ) {
				$caption_tags[ $i ]['link']    = $this->get_link( $match );
				$caption_tags[ $i ]['img']     = current( $this->img_parser->get_imgs( $match ) );
				$caption_tags[ $i ]['caption'] = trim( strip_tags( $match ) );
			}

		}

		return $caption_tags;
	}

	/**
	 * @param string $attributes_list
	 *
	 * @return array
	 */
	private function get_attributes_array( $attributes_list ){
		$attributes = array();
		if ( preg_match_all( '/(\S+)=["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/', $attributes_list, $attribute_matches ) ) {
			foreach ( $attribute_matches[1] as $k => $key ) {
				$attributes[ $key ] = $attribute_matches[2][ $k ];
			}
		}
		return $attributes;
	}

	/**
	 * @param array $attributes
	 *
	 * @return null|int
	 */
	private function get_attachment_id( $attributes ){
		$attachment_id = null;
		if ( isset( $attributes['id'] ) ) {
			if ( preg_match( '/attachment_([0-9]+)\b/', $attributes['id'], $id_match ) ) {
				$attachment_id = $id_match[1];
			}
		}
		return $attachment_id;
	}

	/**
	 * @param string $string
	 *
	 * @return bool|string
	 */
	private function get_link( $string ){
		$link = array();
		if ( preg_match( '/<a ([^>]+)>(.+)<\/a>/s', $string, $a_match ) ) {
			if ( preg_match( '/href=["\']([^"]+)["\']/', $a_match[1], $url_match ) ) {
				$link['url'] = $url_match[1];
			}
		}

		return $link;
	}

}