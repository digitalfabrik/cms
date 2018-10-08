<?php

/**
 * Class WPML_Media_Img_Parse
 */
class WPML_Media_Img_Parse{

	/**
	 * @param string $text
	 *
	 * @return array
	 */
	public function get_imgs( $text ){
		$images = array();

		if( preg_match_all( '/<img ([^>]+)>/s', $text, $matches ) ){
			foreach ( $matches[1] as $i => $match ){
				if( preg_match_all('/(\S+)\\s*=\\s*["\']?((?:.(?!["\']?\s+(?:\S+)=|[>"\']))+.)["\']?/', $match, $attribute_matches ) ){
					$attributes = array();
					foreach( $attribute_matches[1] as $k => $key ){
						$attributes[$key] = $attribute_matches[2][$k];
					}
					if( isset( $attributes['src'] ) ){
						$images[$i]['attributes'] = $attributes;
						$images[$i]['attachment_id'] = $this->get_attachment_id_from_attributes( $images[$i]['attributes'] );
					}
				}
			}
		}

		return $images;
	}

	/**
	 * @param $attributes
	 *
	 * @return null|int
	 */
	private function get_attachment_id_from_attributes( $attributes ){
		$attachment_id = null;
		if( isset( $attributes['class'] ) ){
			if( preg_match('/wp-image-([0-9]+)\b/', $attributes['class'], $id_match ) ){
				if( 'attachment' === get_post_type( (int) $id_match[1] ) ){
					$attachment_id = (int) $id_match[1];
				}
			}
		}
		return $attachment_id;
	}

}