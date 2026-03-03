<?php

namespace EasyBooking;

/**
*
* Settings functions.
* @version 3.0.0
*
**/

defined( 'ABSPATH' ) || exit;

class Settings {

	/**
	*
	* Output or return a select field.
	*
	* @param array $args
	* @return string|void
	*
	**/
	public static function select( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'id'                => '',
				'class'             => '',
				'name'              => '',
				'value'             => '',
				'options'           => array(),
				'custom_attributes' => array(),
				'description'       => '',
				'echo'              => true,
			)
		);

		$attributes = array_merge(
			array(
				'id'    => $args['id'],
				'name'  => $args['name'],
				'class' => $args['class'],
			),
			$args['custom_attributes']
		);

		$html = sprintf(
			'<select%s>',
			self::build_attributes( $attributes )
		);

		foreach ( $args['options'] as $value => $label ) {
			$html .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) $args['value'], (string) $value, false ),
				esc_html( $label )
			);
		}

		$html .= '</select>';

		return self::render_field( $html, $args );

	}

	/**
	*
	* Output or return a checkbox input.
	*
	* @param array $args
	* @return string|void
	*
	**/
	public static function checkbox( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'id'          => '',
				'class'       => '',
				'name'        => '',
				'value'       => '',
				'cbvalue'     => 'yes',
				'description' => '',
				'echo'        => true,
			)
		);

		$attributes = array(
			'type'  => 'checkbox',
			'id'    => $args['id'],
			'name'  => $args['name'],
			'class' => $args['class'],
			'value' => $args['cbvalue'],
		);

		$html = sprintf(
			'<input%s %s />',
			self::build_attributes( $attributes ),
			checked( (string) $args['value'], (string) $args['cbvalue'], false )
		);

		return self::render_field( $html, $args );

	}

	/**
	*
	* Outputs or returns a text input.
	* @param array - $args
	*
	**/
	public static function input( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'type'              => 'text',
				'id'                => '',
				'class'             => '',
				'name'              => '',
				'value'             => '',
				'custom_attributes' => array(),
				'description'       => '',
				'echo'              => true,
			)
		);

		$attributes = array_merge(
			array(
				'type'  => $args['type'],
				'id'    => $args['id'],
				'name'  => $args['name'],
				'class' => $args['class'],
				'value' => $args['value'],
			),
			$args['custom_attributes']
		);

		$html = sprintf(
			'<input%s />',
			self::build_attributes( $attributes )
		);

		return self::render_field( $html, $args );

	}

	/**
	*
	* Outputs or returns a texterea input.
	* @param array - $args
	*
	**/
	public static function textarea( array $args ) {

		$args = wp_parse_args(
			$args,
			array(
				'id'                => '',
				'class'             => '',
				'name'              => '',
				'value'             => '',
				'custom_attributes' => array(),
				'description'       => '',
				'echo'              => true,
			)
		);

		$attributes = array_merge(
			array(
				'id'    => $args['id'],
				'name'  => $args['name'],
				'class' => $args['class'],
			),
			$args['custom_attributes']
		);

		$html = sprintf(
			'<textarea%s>%s</textarea>',
			self::build_attributes( $attributes ),
			esc_textarea( $args['value'] )
		);

		return self::render_field( $html, $args );

	}

	/**
	* 
	* Build HTML attributes string.
	*
	* @param array $attributes
	* @return string
	*
	**/
	private static function build_attributes( array $attributes ): string {

		$output = '';

		foreach ( $attributes as $key => $value ) {

			if ( '' !== $value && null !== $value ) {

				$output .= sprintf(
					' %s="%s"',
					esc_attr( $key ),
					esc_attr( $value )
				);

			}

		}

		return $output;

	}

	/**
	*
	* Render or return field HTML.
	*
	* @param string $html
	* @param array  $args
	* @return string|void
	*
	**/
	private static function render_field( string $html, array $args ) {

		if ( ! empty( $args['description'] ) ) {

			$html .= sprintf(
				'<p class="description">%s</p>',
				wp_kses_post( $args['description'] )
			);

		}

		if ( false === $args['echo'] ) {
			return $html;
		}

		echo $html;

	}

	/**
	*
	* Outputs a (maybe sortable) table with the possibility to add or delete rows.
	* @param str - $content - the content name
	* @param array - $columns
	* @param array - $args
	*
	**/
	public static function table( $content, $columns, $args ) {
		$args = array(
			'table_classes' => isset( $args['table_classes'] ) ? $args['table_classes'] : '',
			'body_classes'  => isset( $args['body_classes'] ) ? $args['body_classes'] : '',
			'row_classes'   => isset( $args['row_classes'] ) ? $args['row_classes'] : '',
			'content'       => isset( $args['content'] ) ? $args['content'] : 'row', 
			'sortable'      => isset( $args['sortable'] ) ? $args['sortable'] : false,
			'delete'        => isset( $args['delete'] ) ? $args['delete'] : true
		);

		$column_count = count( $columns );
		
		if ( true === $args['sortable'] ) {
			$column_count += 1;
		}

		if ( true === $args['delete'] ) {
			$column_count += 1;
		}

		?>

		<table class="<?php echo esc_attr( $args['table_classes'] ); ?>">

			<thead>

	            <tr>

	            	<?php if ( true === $args['sortable'] ) :
	                	echo '<th>&nbsp;</th>';
	            	endif;

	            	if ( $columns ) :

	            		foreach ( $columns as $column ) :

	            			echo '<th>';
	            				echo wp_kses( $column['title'], array( 'span' => 'class' ) );
	            				if ( isset( $column['tip'] ) ) :
	            					echo '<span class="tips" data-tip="' . esc_attr( $column['tip'] ) . '">[?]</span>';
	            				endif;
	            			echo '</th>';

	                	endforeach;

	            	endif;

	            	if ( true === $args['delete'] ) :
	                	echo '<th>&nbsp;</th>';
	            	endif; ?>
	                
	            </tr>

		    </thead>

		    <tbody class="<?php echo esc_attr( $args['body_classes'] ); ?>">
	            <?php if ( $content ) foreach ( $content as $item ) :
	            	echo self::table_columns( $columns, $args, $item );
	            endforeach; ?>
	        </tbody>

	        <tfoot>
	            <tr>
	                <th colspan="<?php echo absint( $column_count ); ?>">
	                    <a href="#" class="button add-row add-<?php echo esc_attr( $args['content'] ); ?>" data-row="<?php
	                        ob_start();
	                        echo self::table_columns( $columns, $args );
	                        echo esc_attr( ob_get_clean() );
	                    ?>"><?php esc_html_e( 'Add', 'woocommerce-easy-booking-system' ); ?></a>
	                </th>
	            </tr>
	        </tfoot>

		</table>

		<?php

	}

	/**
	*
	* Generate columns for the table.
	* @param array - $columns
	* @param array - $args
	* @param array - $item - Existing item
	*
	**/
	public static function table_columns( $columns, $args, $item = array() ) {
		$output = '<tr class="' . esc_attr( $args['row_classes'] ) . '">';

		if ( true === $args['sortable'] ) :
			$output .= '<td class="sort"></td>';
		endif;

		foreach ( $columns as $column ) :

	        $output .= '<td class="' . esc_attr( $args['content'] ) . '_' . esc_attr( $column['name'] ) . '">';

	        	if ( ! empty( $item ) ) {
	        		$column['content']['data']['value'] = $item[$column['name']];
	        	}

	        	$func = $column['content']['function'];

	        	// Backward compatibility
	        	$func = str_replace( 'wceb_settings_', '', $func );
	        	
	        	$output .= self::$func( $column['content']['data'] );

			$output .= '</td>';

		endforeach;

		if ( true === $args['delete'] ) :
			$output .= '<td width="1%"><a href="#" class="delete delete-' . esc_attr( $args['content'] ) . '">' . __( 'Delete', 'woocommerce' ) . '</a></td>';
		endif;

		$output .= '</tr>';

		return $output;

	}

	/**
	*
	* Sanitize checkbox option.
	* @param str - $value
	* @return str - 'yes' or 'no'
	*
	**/
	public static function sanitize_checkbox( $value ) {
		return ( ! empty( $value ) && $value !== 'no' ) ? 'yes' : 'no';
	}

	/**
	*
	* Sanitize duraiton field option.
	* @param str - $value
	* @param int - $min
	* @param int - $max
	* @return int - $value
	*
	**/
	public static function sanitize_duration_field( $value, $min = 0, $max = 3650 ) {
		
		if ( $value < $min ) {
			$value = $min;
		}

		if ( $value > $max ) {
			$value = $max;
		}

		return absint( $value );
	}

}