<?php

abstract class WPML_TM_MCS_Section_UI {

	private $id;
	private $title;

	public function __construct( $id, $title ) {
		$this->id = $id;
		$this->title = $title;
	}

	public function render_top_link() {
		?>
		<a href="#<?php echo esc_attr( $this->id ); ?>"><?php echo esc_html( $this->title ); ?></a>
		<?php
	}

	public function render() {

		?>

		<div class="wpml-section" id="<?php echo esc_attr( $this->id ); ?>">

		    <div class="wpml-section-header">
		        <h3><?php echo esc_html( $this->title ); ?></h3>
			</div>

			<div class="wpml-section-content">
				<?php $this->render_content(); ?>
			</div>

		</div>

		<?php

	}

	protected abstract function render_content();
}

