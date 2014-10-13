<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Renders Membership Plugin Settings.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @uses MS_Helper_Html Helper used to create form elements and vertical navigation.
 *
 * @since 1.0
 *
 * @return object
 */
class MS_View_Settings_Edit extends MS_View {

	protected $fields;

	protected $data;

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * Creates a wrapper 'ms-wrap' HTML element to contain content and navigation. The content inside
	 * the navigation gets loaded with dynamic method calls.
	 * e.g. if key is 'settings' then render_settings() gets called, if 'bob' then render_bob().
	 *
	 * @todo Could use callback functions to call dynamic methods from within the helper, thus
	 * creating the navigation with a single method call and passing method pointers in the $tabs array.
	 *
	 * @since 4.0.0
	 *
	 * @return object
	 */
	public function to_html() {
		// Setup navigation tabs.
		$tabs = $this->data['tabs'];

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<?php
			MS_Helper_Html::settings_header(
				array(
					'title' => __( 'Protect Content Settings', MS_TEXT_DOMAIN ),
					'title_icon_class' => 'fa fa-cog',
				)
			);
			$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );

			// Call the appropriate form to render.
			$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
			$render_callback = apply_filters(
				'ms_view_settings_edit_render_callback',
				array( $this, $callback_name ),
				$active_tab,
				$this->data
			);

			$html = call_user_func( $render_callback );
			$html = apply_filters( 'ms_view_settings_' . $callback_name, $html );
			echo $html;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               GENERAL
	 * ====================================================================== */

	public function render_tab_general() {
		$settings = $this->data['settings'];
		$fields = array(
			'plugin_enabled' => array(
				'id' => 'plugin_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'This setting enable/disable the membership plugin protection.', MS_TEXT_DOMAIN ),
				'value' => $settings->plugin_enabled,
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'plugin_enabled',
				),
			),

			'hide_admin_bar' => array(
				'id' => 'hide_admin_bar',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Hide admin bar for non administrator users.', MS_TEXT_DOMAIN ),
				'value' => $settings->hide_admin_bar,
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'hide_admin_bar',
				),
			),

			'initial_setup' => array(
				'id' => 'initial_setup',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Enable wizard.', MS_TEXT_DOMAIN ),
				'value' => $settings->initial_setup,
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'initial_setup',
				),
			),
		);
		$fields = apply_filters( 'ms_view_settings_prepare_general_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'General Settings', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form action="" method="post">
				<?php
				MS_Helper_Html::settings_box(
					array( $fields['plugin_enabled'] ),
					__( 'Enable plugin', MS_TEXT_DOMAIN )
				);

				MS_Helper_Html::settings_box(
					array( $fields['hide_admin_bar'] ),
					__( 'Hide admin bar', MS_TEXT_DOMAIN )
				);

				MS_Helper_Html::settings_box(
					array( $fields['initial_setup'] ),
					__( 'Enable wizard', MS_TEXT_DOMAIN )
				);
				?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAGES
	 * ====================================================================== */

	public function render_tab_pages() {

		$action = MS_Controller_Page::AJAX_ACTION_UPDATE_PAGE;
		$nonce = wp_create_nonce( $action );

		$ms_pages = $this->data['ms_pages'];
		
		$fields = array();
		foreach( $ms_pages as $ms_page ) {
			$fields['pages'][ $ms_page->type ] = array(
					'id' => $ms_page->type,
					'page_id' => $ms_page->id,
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'read_only' => true,
					'title' => sprintf( __( 'Select %s page', MS_TEXT_DOMAIN ), $ms_page->title ),
					'value' => sprintf( '/%1$s/', $ms_page->slug ),
					'class' => 'ms-ajax-update',
					'data_ms' => array(
							'page_type' => $ms_page->type,
							'field' => 'slug',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			);
			$fields['edit'][ $ms_page->type ] = array(
					'id' => 'edit_slug_' . $ms_page->type,
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Edit URL', MS_TEXT_DOMAIN ),
					'class' => 'ms-edit-url',
			);

		}

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php 
				MS_Helper_Html::settings_tab_header( array( 
					'title' => __( 'Page Settings', MS_TEXT_DOMAIN ) 
				) ); 
			?>
			<div class="ms-separator"></div>

			<form action="" method="post">

				<?php foreach ( $fields['pages'] as $page_type => $field ) : ?>
					<?php
						MS_Helper_Html::html_element( $field );
						MS_Helper_Html::html_element( $fields['edit'][ $page_type ] );
					?>
					<div id="ms-settings-page-links-wrapper">
						<?php
						MS_Helper_Html::html_link(
							array(
								'id' => 'url_page_' . $field['page_id'],
								'url' => get_permalink( $field['page_id'] ),
								'value' => __( 'View Page', MS_TEXT_DOMAIN ),
							)
						);
						?>
						<span> | </span>
						<?php
						MS_Helper_Html::html_link(
							array(
								'id' => 'edit_url_page_' . $field['page_id'],
								'url' => get_edit_post_link( $field['page_id'] ),
								'value' => __( 'Edit Page', MS_TEXT_DOMAIN ),
							)
						);
						?>
					</div>
				<?php endforeach; ?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAYMENT
	 * ====================================================================== */

	public function render_tab_payment() {
		$view = MS_Factory::create( 'MS_View_Settings_Payment' );

		ob_start();
		?>
		<div class="ms-settings">
			<div id="ms-payment-settings-wrapper">
				<?php $view->render(); ?>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PROTECTION MESSAGE
	 * ====================================================================== */

	public function render_tab_messages_protection() {
		$settings = $this->data['settings'];
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'content' => array(
				'editor' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'content',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'shortcode' => array(
				'editor' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'shortcode',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'more_tag' => array(
				'editor' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary ms-ajax-update',
					'data_ms' => array(
						'type' => 'more_tag',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		$membership = $this->data['membership'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$has_more = $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form class="ms-form" action="" method="post">
				<?php
				MS_Helper_Html::settings_box(
					$fields['content'],
					__( 'Content protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);

				MS_Helper_Html::settings_box(
					$fields['shortcode'],
					__( 'Shortcode protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);

				if ( $has_more ) {
					MS_Helper_Html::settings_box(
						$fields['more_tag'],
						__( 'More tag protection message', MS_TEXT_DOMAIN ),
						'',
						'open'
					);
				}
				?>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               AUTOMATED MESSAGES
	 * ====================================================================== */

	public function render_tab_messages_automated() {
		$comm = $this->data['comm'];

		$action = MS_Controller_Communication::AJAX_ACTION_UPDATE_COMM;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'comm_type' => array(
				'id' => 'comm_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->type,
				'field_options' => MS_Model_Communication::get_communication_type_titles(),
				'class' => '',
			),

			'type' => array(
				'id' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $comm->type,
			),

			'enabled' => array(
				'id' => 'enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Enabled', MS_TEXT_DOMAIN ),
				'value' => $comm->enabled,
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_unit' => array(
				'id' => 'period_unit',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Period after/before', MS_TEXT_DOMAIN ),
				'value' => $comm->period['period_unit'],
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'period_unit',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_type' => array(
				'id' => 'period_type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->period['period_type'],
				'field_options' => MS_Helper_Period::get_periods(),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'period_type',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'subject' => array(
				'id' => 'subject',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Message Subject', MS_TEXT_DOMAIN ),
				'value' => $comm->subject,
				'class' => 'ms-comm-subject ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'subject',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'message' => array(
				'id' => 'message',
				'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
				'title' => __( 'Message', MS_TEXT_DOMAIN ),
				'value' => $comm->description,
				'field_options' => array( 'media_buttons' => false, 'editor_class' => 'ms-ajax-update' ),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'message',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'cc_enabled' => array(
				'id' => 'cc_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'title' => __( 'Send copy to Administrator', MS_TEXT_DOMAIN ),
				'value' => $comm->cc_enabled,
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'cc_enabled',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'cc_email' => array(
				'id' => 'cc_email',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $comm->cc_email,
				'field_options' => MS_Model_Member::get_admin_user_emails(),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'type' => $comm->type,
					'field' => 'cc_email',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'save_email' => array(
				'id' => 'save_email',
				'value' => __( 'Save Automated Email', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			),

			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'save_comm',
			),

			'nonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'save_comm' ),
			),

			'load_action' => array(
				'id' => 'load_action',
				'name' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'load_action',
			),

			'load_nonce' => array(
				'id' => '_wpnonce1',
				'name' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( 'load_action' ),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_messages_automated_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Automated Messages', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<form id="ms-comm-type-form" action="" method="post">
				<?php MS_Helper_Html::html_element( $fields['load_action'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['load_nonce'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['comm_type'] ); ?>
				<p><?php echo $comm->get_description(); ?></p>
			</form>

			<form action="" method="post">
				<?php MS_Helper_Html::html_element( $fields['action'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['nonce'] ); ?>
				<?php MS_Helper_Html::html_element( $fields['type'] ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<?php MS_Helper_Html::html_element( $fields['enabled'] ); ?>
							</td>
						</tr>
						<?php if ( $comm->period_enabled ) : ?>
							<tr>
								<td>
									<div class="ms-period-wrapper">
										<?php MS_Helper_Html::html_element( $fields['period_unit'] ); ?>
										<?php MS_Helper_Html::html_element( $fields['period_type'] ); ?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
						<tr>
							<td>
								<?php MS_Helper_Html::html_element( $fields['subject'] ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<div id="ms-comm-message-wrapper">
								<?php MS_Helper_Html::html_element( $fields['message'] ); ?>
								</div>
								<div id="ms-comm-var-wrapper">
									<table>
										<tr>
											<th>Variable values</th>
										</tr>
										<?php foreach ( $comm->comm_vars as $var => $description ) : ?>
											<tr>
												<td>
													<?php MS_Helper_html::tooltip( $description ); ?>
													<?php echo $var; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</table>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_element( $fields['cc_enabled'] ); ?>
								<?php MS_Helper_Html::html_element( $fields['cc_email'] ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php MS_Helper_Html::html_separator(); ?>
								<?php MS_Helper_Html::html_element( $fields['save_email'] ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               MEDIA / DOWNLOADS
	 * ====================================================================== */

	public function render_tab_downloads() {
		$upload_dir = wp_upload_dir();
		$settings = $this->data['settings'];

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'protection_type' => array(
				'id' => 'protection_type',
				'name' => 'downloads[protection_type]',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'Protection method', MS_TEXT_DOMAIN ),
				'value' => $settings->downloads['protection_type'],
				'field_options' => MS_Model_Rule_Media::get_protection_types(),
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'field' => 'protection_type',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),
			'upload_url' => array(
				'id' => 'mailchimp_api_test',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Current upload location', MS_TEXT_DOMAIN ),
				'value' => trailingslashit( $upload_dir['baseurl'] ),
				'wrapper' => 'div',
				'class' => '',
			),
			'masked_url' => array(
				'id' => 'masked_url',
				'name' => 'downloads[masked_url]',
				'desc' => esc_html( trailingslashit( get_option( 'home' ) ) ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Masked download url', MS_TEXT_DOMAIN ),
				'value' => $settings->downloads['masked_url'],
				'class' => 'ms-ajax-update',
				'data_ms' => array(
					'field' => 'masked_url',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_downloads_fields', $fields );

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Media / Download Settings', MS_TEXT_DOMAIN ) )
			); ?>
			<div class="ms-separator"></div>

			<div>
				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}