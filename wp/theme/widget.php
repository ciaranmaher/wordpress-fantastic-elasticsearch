<?php
class FacetingOptionsWidget extends \WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'Faceting: Options' );
	}

	function widget( $args, $instance ) {
		global $wp_query;

		$async = isset($instance['async']) && $instance['async'];

		if($async){
			wp_enqueue_script("jquery");
			wp_enqueue_script('elasticsearch', plugins_url('/js/ajax.js', __FILE__), array( 'jquery' ));
		}
		
		$facets = elasticsearch\Faceting::all();

		$prep = array();

		$url = get_permalink();
		$selected = null;

		if(is_category()){
			$term =	$wp_query->queried_object;

			$url = get_term_link($term);
		}

		foreach($facets as $type => $facet){
			if($facet['total'] > 0 || $async){
				foreach($facet['available'] as $option){
					if($option['count'] != $wp_query->post_count){
						if(!isset($prep[$type])){
							$name = $type;

							if(taxonomy_exists($type)){
								$name = get_taxonomy($type)->label;
							}

							$prep[$type] = array(
								'type' => $type,
								'name' => $name,
								'avail' => array(),
								'show' => false
							);
						}

						if($option['count'] > 1){
							$prep[$type]['show'] = true;
						}

						$prep[$type]['avail'][] = array(
							'url' => elasticsearch\Faceting::urlAdd($url, $type, $option['slug']),
							'option' => $option
						);
					}
				}
			}
		}

		if(count($prep) > 0){
			echo '<form action="' . $url . '" method="GET" id="esajaxform">';

			foreach($prep as $type => $settings){
				if(is_category() && $type == 'category'){
					continue;
				}

				$style = $settings['show'] ? '' : 'style="display:none"';

				echo '<aside id="facet-' . $type . '-available" class="widget facets facets-available" ' . $style . '>';

				echo '<h3 class="widget-title">' . $settings['name'] . '</h3>';

				if($async){
					echo '<p class="facet-empty" style="display:none">You can filter the results anymore</p>';
				}

				echo '<ul>';

				foreach($settings['avail'] as $avail){
					$style = $avail['option']['count'] > 1 ? '' : 'style="display:none"';

					echo '<li id="facet-' . $type . '-' . $avail['option']['slug'] . '" class="facet-item" ' . $style . '>';

					if($async){
						printf('<input type="checkbox" name="%s[and][]" value="%s" />%s <span class="count">(%d)</span>', $type, $avail['option']['slug'],
							$avail['option']['name'], $avail['option']['count']);
					}else{
						echo '<a href="' . $avail['url'] . '">' . $avail['option']['name'] . ' (' . $avail['option']['count'] . ')</a>';
					}

					echo '</li>';
				}

				echo '</ul>';

				echo '</aside>';
			}

			echo '</form>';
		}
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		?>
			<p>  
			    <input class="checkbox" type="checkbox" <?php checked( $instance['async'], true ); ?>
			    	id="<?php echo $this->get_field_id( 'async' ); ?>" name="<?php echo $this->get_field_name( 'async' ); ?>" value="1" />   
			    <label for="<?php echo $this->get_field_id( 'async' ); ?>">Update page content asynchronously</label>  
			</p>  
		<?
	}
}

class FactingSelectedWidget extends \WP_Widget {

	function __construct() {
		// Instantiate the parent object
		parent::__construct( false, 'Faceting: Selected' );
	}

	function widget( $args, $instance ) {
		global $wp_query;

		$facets = elasticsearch\Faceting::all();

		$url = get_permalink();

		if(is_category()){
			$term =	$wp_query->queried_object;

			$url = get_term_link($term);
		}

		foreach($facets as $type => $facet){
			if(count($facet['selected']) > 0){
				$name = $type;

				if(taxonomy_exists($type)){
					$name = get_taxonomy($type)->label;
				}

				echo '<aside id="facet-' . $type . '-selected" class="widget facets facets-selected">';

				echo '<h3 class="widget-title">' . $name . '</h3>';

				echo '<ul>';

				foreach($facet['selected'] as $option){
					$url = elasticsearch\Faceting::urlRemove($url, $type, $option['slug']);

					echo '<li id="facet-' . $type . '-' . $option['slug'] . '" class="facet-item">';
					echo '<a href="' . $url . '">' . $option['name'] . '</a>';
					echo '</li>';
				}

				echo '</ul>';

				echo '</aside>';
			}
		}
	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
	}

	function form( $instance ) {
		// Output admin widget options form
	}
}

add_action( 'widgets_init', function() {
	register_widget( 'FacetingOptionsWidget' );
	register_widget( 'FactingSelectedWidget' );
});

?>