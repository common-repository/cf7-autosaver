<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Autosaver_For_CF7_Settings Class
 *
 * @class Autosaver_For_CF7_Settings
 * @version	1.0.0
 * @since 1.0.0
 * @package	Autosaver_For_CF7
 * @author Bishoy A.
 */
final class Autosaver_For_CF7_Settings {
	/**
	 * Autosaver_For_CF7_Admin The single instance of Autosaver_For_CF7_Admin.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Whether or not a 'select' field is present.
	 * @var     boolean
	 * @access  private
	 * @since   1.0.0
	 */
	private $_has_select;

	/**
	 * Main Autosaver_For_CF7_Settings Instance
	 *
	 * Ensures only one instance of Autosaver_For_CF7_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return Main Autosaver_For_CF7_Settings instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
	} // End __construct()

	/**
	 * Validate the settings.
	 * @access  public
	 * @since   1.0.0
	 * @param   array $input Inputted data.
	 * @param   string $section field section.
	 * @return  array        Validated data.
	 */
	public function validate_settings ( $input, $section ) {
		if ( is_array( $input ) && 0 < count( $input ) ) {
			$fields = $this->get_settings_fields( $section );

			foreach ( $input as $k => $v ) {
				if ( ! isset( $fields[$k] ) ) {
					continue;
				}

				// Determine if a method is available for validating this field.
				$method = 'validate_field_' . $fields[$k]['type'];

				if ( ! method_exists( $this, $method ) ) {
					if ( true === (bool)apply_filters( 'autosaver-for-cf7-validate-field-' . $fields[$k]['type'] . '_use_default', true ) ) {
						$method = 'validate_field_text';
					} else {
						$method = '';
					}
				}

				// If we have an internal method for validation, filter and apply it.
				if ( '' != $method ) {
					add_filter( 'autosaver-for-cf7-validate-field-' . $fields[$k]['type'], array( $this, $method ) );
				}

				$method_output = apply_filters( 'autosaver-for-cf7-validate-field-' . $fields[$k]['type'], $v, $fields[$k] );

				if ( ! is_wp_error( $method_output ) ) {
					$input[$k] = $method_output;
				}
			}
		}
		return $input;
	} // End validate_settings()

	/**
	 * Validate the given data, assuming it is from a text input field.
	 * @access  public
	 * @since   6.0.0
	 * @return  void
	 */
	public function validate_field_text ( $v ) {
		return (string)wp_kses_post( $v );
	} // End validate_field_text()

	/**
	 * Validate the given data, assuming it is from a select input field.
	 * @access  public
	 * @since   6.0.0
	 * @return  void
	 */
	public function validate_field_select_multiple ( $v ) {
		return array_map('wp_kses_post', $v);
	} // End validate_field_text()

	/**
	 * Validate the given data, assuming it is from a textarea field.
	 * @access  public
	 * @since   6.0.0
	 * @return  void
	 */
	public function validate_field_textarea ( $v ) {
		// Allow iframe, object and embed tags in textarea fields.
		$allowed 			= wp_kses_allowed_html( 'post' );
		$allowed['iframe'] 	= array(
								'src' 		=> true,
								'width' 	=> true,
								'height' 	=> true,
								'id' 		=> true,
								'class' 	=> true,
								'name' 		=> true
								);
		$allowed['object'] 	= array(
								'src' 		=> true,
								'width' 	=> true,
								'height' 	=> true,
								'id' 		=> true,
								'class' 	=> true,
								'name' 		=> true
								);
		$allowed['embed'] 	= array(
								'src' 		=> true,
								'width' 	=> true,
								'height' 	=> true,
								'id' 		=> true,
								'class' 	=> true,
								'name' 		=> true
								);

		return wp_kses( $v, $allowed );
	} // End validate_field_textarea()

	/**
	 * Validate the given data, assuming it is from a checkbox input field.
	 * @access public
	 * @since  6.0.0
	 * @param  string $v
	 * @return string
	 */
	public function validate_field_checkbox ( $v ) {
		if ( 'true' != $v ) {
			return 'false';
		} else {
			return 'true';
		}
	} // End validate_field_checkbox()

	/**
	 * Validate the given data, assuming it is from a URL field.
	 * @access public
	 * @since  6.0.0
	 * @param  string $v
	 * @return string
	 */
	public function validate_field_url ( $v ) {
		return trim( esc_url( $v ) );
	} // End validate_field_url()

	/**
	 * Render a field of a given type.
	 * @access  public
	 * @since   1.0.0
	 * @param   array $args The field parameters.
	 * @return  void
	 */
	public function render_field ( $args ) {
		$html = '';
		if ( ! in_array( $args['type'], $this->get_supported_fields() ) ) return ''; // Supported field type sanity check.

		// Make sure we have some kind of default, if the key isn't set.
		if ( ! isset( $args['default'] ) ) {
			$args['default'] = '';
		}

		$method = 'render_field_' . $args['type'];

		if ( ! method_exists( $this, $method ) ) {
			$method = 'render_field_text';
		}

		// Construct the key.
		$key 				= Autosaver_For_CF7()->token . '-' . $args['section'] . '[' . $args['id'] . ']';
		$method_output 		= $this->$method( $key, $args );

		if ( ! is_wp_error( $method_output ) ) {
			$html .= $method_output;
		}

		// Output the description, if the current field allows it.
		if ( isset( $args['type'] ) && ! in_array( $args['type'], (array)apply_filters( 'autosaver-for-cf7-no-description-fields', array( 'checkbox' ) ) ) ) {
			if ( isset( $args['description'] ) ) {
				$description = '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>' . "\n";
				if ( in_array( $args['type'], (array)apply_filters( 'autosaver-for-cf7-new-line-description-fields', array( 'textarea', 'select' ) ) ) ) {
					$description = wpautop( $description );
				}
				$html .= $description;
			}
		}

		echo $html;
	} // End render_field()

	/**
	 * Retrieve the settings fields details
	 * @access  public
	 * @since   1.0.0
	 * @return  array        Settings fields.
	 */
	public function get_settings_sections () {
		$settings_sections = array();

		$settings_sections['autosaver']          = __( 'Autosaver', 'autosaver-for-cf7' );
		$settings_sections['fill-with-facebook'] = __( 'Fill With Facebook', 'autosaver-for-cf7' );

		return (array)apply_filters( 'autosaver-for-cf7-settings-sections', $settings_sections );
	} // End get_settings_sections()

	/**
	 * Retrieve the settings fields details
	 * @access  public
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  array        Settings fields.
	 */
	public function get_settings_fields ( $section ) {
		$settings_fields = array();
		// Declare the default settings fields.

		if ( class_exists( 'WPCF7_ContactForm' ) ) {
			$forms_args = array(
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'offset'         => 0,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_type'      => WPCF7_ContactForm::post_type
			);

			$forms = get_posts( $forms_args );

			$forms_options = array();

			foreach ( $forms as $form ) {
				$forms_options[ $form->ID ] = $form->post_title;
			}
		}

		switch ( $section ) {
			case 'autosaver':
				
				$settings_fields['cf7r_form_select'] = array(
						'name' => __( 'Contact Form 7 form', 'autosaver-for-cf7' ),
						'type' => 'select_multiple',
						'default' => '',
						'section' => 'autosaver',
						'options' => $forms_options,
						'description' => __( 'Select Contact Form 7 forms that you\'d like to be auto-saved. Please note that the key of saving is the field name, so if multiple forms have the exact same field names they will have the same user value. You can add the class <code>ascf7-excluded</code> to any form field to exclude it from the autosave process.', 'autosaver-for-cf7' )
				);
				$settings_fields['cf7r_location_based'] = array(
					'name' => __( 'Location Based?', 'autosaver-for-cf7' ),
					'type' => 'checkbox',
					'default' => '',
					'section' => 'autosaver',
					'description' => __( 'If checked, different URLs will have different values even for the same forms.', 'autosaver-for-cf7' )
				);
				$settings_fields['cf7r_auto_release'] = array(
					'name' => __( 'Auto reset?', 'autosaver-for-cf7' ),
					'type' => 'checkbox',
					'default' => '',
					'section' => 'autosaver',
					'description' => __( 'If not checked, forms would still be saved even after user submit.', 'autosaver-for-cf7' )
				);

				break;
			case 'fill-with-facebook':

				$settings_fields['cf7_fb_form_select'] = array(
						'name' => __( 'Contact Form 7 form', 'autosaver-for-cf7' ),
						'type' => 'select_multiple',
						'default' => '',
						'section' => 'fill-with-facebook',
						'options' => $forms_options,
						'description' => __( 'Select Contact Form 7 forms that you\'d like to be auto-saved allowed the "Fill With Facebook". This will allow users to auto-fill the above-selected forms with their Facebook data. For this to work, you need to add "fb" classes to your form fields, <em>"cf7as-fb-first-name", "cf7as-fb-last-name", "cf7as-fb-email"</em>. Example: [text* your-name <strong>class:cf7as-fb-first-name</strong>]', 'autosaver-for-cf7' )
				);

				$settings_fields['cf7r_facebook_app'] = array(
					'name' => __( 'Facebook App ID', 'autosaver-for-cf7' ),
					'type' => 'text',
					'default' => '',
					'section' => 'fill-with-facebook',
					'description' => __( 'Please note that this will initialize a JS FB SDK, if you already have a FB SDK enabled this could cause conflicts.', 'autosaver-for-cf7' ) . ' ' . '<a href="https://developers.facebook.com/apps/" target="_blank">' . __( 'Click here to create an application.' ) . '</a>'
				);
				$settings_fields['cf7r_facebook_link_text'] = array(
					'name' => __( 'Facebook Fill Text', 'autosaver-for-cf7' ),
					'type' => 'text',
					'default' => '',
					'section' => 'fill-with-facebook',
					'description' => __( '"Fill With Facebook" Link text. Default is "Fill With Facebook".', 'autosaver-for-cf7' )
				);
				$settings_fields['cf7r_facebook_link_above'] = array(
					'name' => __( 'Show Facebook Link Above Form?', 'autosaver-for-cf7' ),
					'type' => 'checkbox',
					'default' => '',
					'section' => 'fill-with-facebook',
					'description' => __( 'If not checked, link will show at the bottom after the form.', 'autosaver-for-cf7' )
				);

				break;
			default:
				break;
		}

		return (array)apply_filters( 'autosaver-for-cf7-settings-fields', $settings_fields );
	} // End get_settings_fields()

	/**
	 * Render HTML markup for the "text" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_text ( $key, $args ) {
		$html = '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" size="40" type="text" value="' . esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ) . '" />' . "\n";
		return $html;
	} // End render_field_text()

	/**
	 * Render HTML markup for the "radio" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_radio ( $key, $args ) {
		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array)$args['options'] ) ) ) {
			$html = '';
			foreach ( $args['options'] as $k => $v ) {
				$html .= '<input type="radio" name="' . esc_attr( $key ) . '" value="' . esc_attr( $k ) . '"' . checked( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), $k, false ) . ' /> ' . esc_html( $v ) . '<br />' . "\n";
			}
		}
		return $html;
	} // End render_field_radio()

	/**
	 * Render HTML markup for the "textarea" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_textarea ( $key, $args ) {
		// Explore how best to escape this data, as esc_textarea() strips HTML tags, it seems.
		$html = '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" cols="42" rows="5">' . $this->get_value( $args['id'], $args['default'], $args['section'] ) . '</textarea>' . "\n";
		return $html;
	} // End render_field_textarea()

	/**
	 * Render HTML markup for the "checkbox" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_checkbox ( $key, $args ) {
		$has_description = false;
		$html = '';
		if ( isset( $args['description'] ) ) {
			$has_description = true;
			$html .= '<label for="' . esc_attr( $key ) . '">' . "\n";
		}
		$html .= '<input id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="checkbox" value="true"' . checked( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), 'true', false ) . ' />' . "\n";
		if ( $has_description ) {
			$html .= wp_kses_post( $args['description'] ) . '</label>' . "\n";
		}
		return $html;
	} // End render_field_checkbox()

	/**
	 * Render HTML markup for the "select2" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_select ( $key, $args ) {
		$this->_has_select = true;

		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array)$args['options'] ) ) ) {
			$html .= '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . "\n";
				foreach ( $args['options'] as $k => $v ) {
					$html .= '<option value="' . esc_attr( $k ) . '"' . selected( esc_attr( $this->get_value( $args['id'], $args['default'], $args['section'] ) ), $k, false ) . '>' . esc_html( $v ) . '</option>' . "\n";
				}
			$html .= '</select>' . "\n";
		}
		return $html;
	} // End render_field_select()

	/**
	 * Render HTML markup for the "select_multiple" field type.
	 * @access  protected
	 * @since   6.0.0
	 * @param   string $key  The unique ID of this field.
	 * @param   array $args  Arguments used to construct this field.
	 * @return  string       HTML markup for the field.
	 */
	protected function render_field_select_multiple ( $key, $args ) {
		$this->_has_select = true;

		$html = '';
		if ( isset( $args['options'] ) && ( 0 < count( (array)$args['options'] ) ) ) {
			$html .= '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '[]" multiple="multiple">' . "\n";
				foreach ( $args['options'] as $k => $v ) {
					$html .= '<option value="' . esc_attr( $k ) . '"' . selected( true, in_array( $k, $this->get_value( $args['id'], $args['default'], $args['section'] ) ), false ) . '>' . esc_html( $v ) . '</option>' . "\n";
				}
			$html .= '</select>' . "\n";
		}
		return $html;
	} // End render_field_select_multiple()

	/**
	 * Return an array of field types expecting an array value returned.
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_array_field_types () {
		return array();
	} // End get_array_field_types()

	/**
	 * Return an array of field types where no label/header is to be displayed.
	 * @access protected
	 * @since  1.0.0
	 * @return array
	 */
	protected function get_no_label_field_types () {
		return array( 'info' );
	} // End get_no_label_field_types()

	/**
	 * Return a filtered array of supported field types.
	 * @access  public
	 * @since   1.0.0
	 * @return  array Supported field type keys.
	 */
	public function get_supported_fields () {
		return (array)apply_filters( 'autosaver-for-cf7-supported-fields', array( 'text', 'checkbox', 'radio', 'textarea', 'select', 'select_multiple' ) );
	} // End get_supported_fields()

	/**
	 * Return a value, using a desired retrieval method.
	 * @access  public
	 * @param  string $key option key.
	 * @param  string $default default value.
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	public function get_value ( $key, $default, $section ) {
		$values = get_option( 'autosaver-for-cf7-' . $section, array() );

		if ( is_array( $values ) && isset( $values[$key] ) ) {
			$response = $values[$key];
		} else {
			$response = $default;
		}

		return $response;
	} // End get_value()

	/**
	 * Return all settings keys.
	 * @access  public
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	public function get_settings ( $section = '' ) {
		$response = false;

		$sections = array_keys( (array)$this->get_settings_sections() );

		if ( in_array( $section, $sections ) ) {
			$sections = array( $section );
		}

		if ( 0 < count( $sections ) ) {
			foreach ( $sections as $k => $v ) {
				$fields = $this->get_settings_fields( $v );
				$values = get_option( 'autosaver-for-cf7-' . $v, array() );

				if ( is_array( $fields ) && 0 < count( $fields ) ) {
					foreach ( $fields as $i => $j ) {
						// If we have a value stored, use it.
						if ( isset( $values[$i] ) ) {
							$response[$i] = $values[$i];
						} else {
							// Otherwise, check for a default value. If we have one, use it. Otherwise, return an empty string.
							if ( isset( $fields[$i]['default'] ) ) {
								$response[$i] = $fields[$i]['default'];
							} else {
								$response[$i] = '';
							}
						}
					}
				}
			}
		}

		return $response;
	} // End get_settings()
} // End Class
