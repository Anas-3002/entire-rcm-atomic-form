<?php
/**
 * Atomic_Form — a native Elementor v4 (atomic) form element.
 *
 * @package EntireRCM\AtomicWidgets
 */

namespace EntireRCM\AtomicWidgets;

use Elementor\Modules\AtomicWidgets\Controls\Section;
use Elementor\Modules\AtomicWidgets\Controls\Types\Text_Control;
use Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base;
use Elementor\Modules\AtomicWidgets\PropTypes\Attributes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Classes_Prop_Type;
use Elementor\Modules\AtomicWidgets\PropTypes\Primitives\String_Prop_Type;
use Elementor\Modules\AtomicWidgets\Styles\Style_Definition;
use Elementor\Modules\AtomicWidgets\Styles\Style_Variant;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a form — or any shortcode — as a first-class atomic element.
 *
 * Deliberately NOT using the `Has_Template` trait: that trait builds a fixed
 * Twig context, so a template has no way to call `do_shortcode()`. Implementing
 * `render()` in PHP gives us the shortcode pipeline while still emitting the
 * exact wrapper the atomic system expects (classes + base style + attributes +
 * interaction id), so styling, interactions and the editor all keep working.
 */
class Atomic_Form extends Atomic_Widget_Base {

	/**
	 * Stable element id. This is the type written into `_elementor_data` and the
	 * name the MCP sees, so it must not change after pages are built with it.
	 */
	public static function get_element_type(): string {
		return 'e-rcm-form';
	}

	public function get_title() {
		return esc_html__( 'Form', 'entire-rcm-atomic-form' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_keywords() {
		return [ 'form', 'contact', 'cf7', 'shortcode', 'atomic' ];
	}

	/**
	 * Prop schema. Every key here is a key the editor and the MCP may write.
	 *
	 * `classes` and `attributes` are the standard pair every atomic element
	 * carries; without them the element cannot be styled or given a CSS id.
	 */
	protected static function define_props_schema(): array {
		$attributes = Attributes_Prop_Type::make();

		// Components is a Pro module; only mark the prop overridable if present,
		// otherwise referencing the class would fatal on a free install.
		if ( class_exists( '\Elementor\Modules\Components\PropTypes\Overridable_Prop_Type' ) ) {
			$attributes = $attributes->meta(
				\Elementor\Modules\Components\PropTypes\Overridable_Prop_Type::ignore()
			);
		}

		return [
			'classes'    => Classes_Prop_Type::make()->default( [] ),
			'form_id'    => String_Prop_Type::make()->default( '' ),
			'shortcode'  => String_Prop_Type::make()->default( '' ),
			'attributes' => $attributes,
		];
	}

	/**
	 * Editor panel. Control keys MUST match schema keys exactly — `bind_to()` is
	 * how the editor knows which prop a control writes.
	 */
	protected function define_atomic_controls(): array {
		return [
			Section::make()
				->set_label( __( 'Form', 'entire-rcm-atomic-form' ) )
				->set_id( 'content' )
				->set_items(
					[
						Text_Control::bind_to( 'form_id' )
							->set_label( __( 'Contact Form 7 ID', 'entire-rcm-atomic-form' ) ),
						Text_Control::bind_to( 'shortcode' )
							->set_label( __( 'Or any shortcode (overrides the ID above)', 'entire-rcm-atomic-form' ) ),
					]
				),
		];
	}

	/**
	 * A base style variant gives the element a stable generated class, so it can
	 * be targeted by CSS and shows up styled in the editor before any custom
	 * class is applied.
	 */
	protected function define_base_styles(): array {
		return [
			'base' => Style_Definition::make()
				->add_variant( Style_Variant::make() ),
		];
	}

	/**
	 * Render. Mirrors what `Has_Template::render()` emits, but in PHP so the
	 * shortcode can actually execute.
	 */
	protected function render() {
		$settings = $this->get_atomic_settings();
		$base     = $this->get_base_styles_dictionary();
		$classes  = $this->compose_classes( $settings, $base );
		$shortcode = $this->resolve_shortcode( $settings );

		$id_attribute = ! empty( $settings['_cssid'] )
			? 'id="' . esc_attr( $settings['_cssid'] ) . '"'
			: '';

		// `attributes` is pre-rendered and sanitised upstream by Attributes_Prop_Type.
		$extra_attributes = isset( $settings['attributes'] ) ? (string) $settings['attributes'] : '';
		?>
		<div class="<?php echo esc_attr( $classes ); ?>"
			data-interaction-id="<?php echo esc_attr( $this->get_interaction_id() ); ?>"
			<?php echo $id_attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped upstream. ?>
			<?php echo $extra_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped upstream. ?>
		>
			<?php
			if ( '' === $shortcode ) {
				$this->render_placeholder();
			} else {
				echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output is trusted plugin output.
			}
			?>
		</div>
		<?php
	}

	/**
	 * Build the class list: the user's classes plus the generated base class.
	 */
	private function compose_classes( array $settings, array $base ): string {
		$classes = isset( $settings['classes'] ) ? (array) $settings['classes'] : [];

		if ( ! empty( $base['base'] ) ) {
			$classes[] = $base['base'];
		}

		$classes = array_filter( array_map( 'strval', $classes ) );

		return trim( implode( ' ', $classes ) );
	}

	/**
	 * A raw shortcode wins; otherwise build a Contact Form 7 shortcode from the
	 * id. Keeping both means this element also covers maps, embeds and anything
	 * else v4 has no native element for.
	 */
	private function resolve_shortcode( array $settings ): string {
		$shortcode = trim( (string) ( $settings['shortcode'] ?? '' ) );

		if ( '' !== $shortcode ) {
			return $shortcode;
		}

		$form_id = trim( (string) ( $settings['form_id'] ?? '' ) );

		if ( '' === $form_id ) {
			return '';
		}

		return sprintf( '[contact-form-7 id="%d"]', (int) $form_id );
	}

	/**
	 * Editors see what is missing; visitors see nothing, so an unconfigured
	 * element never leaks a debug message onto the live page.
	 */
	private function render_placeholder(): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		echo '<div style="padding:18px;border:1px dashed #b9bfd0;border-radius:8px;'
			. 'font:13px/1.5 system-ui,sans-serif;color:#5b6478;">'
			. '<strong>Form element:</strong> set a <em>Contact Form 7 ID</em> '
			. '(or paste a shortcode) in the panel to render the form here.</div>';
	}
}
