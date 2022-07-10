<?php
/***********************************************************************************************************************
 * Copyright 2021 - 2022, Inesonic, LLC
 *
 * GNU Public License, Version 3:
 *   This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 *   License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any
 *   later version.
 *
 *   This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 *   warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 *   details.
 *
 *   You should have received a copy of the GNU General Public License along with this program.  If not, see
 *   <https://www.gnu.org/licenses/>.
 ***********************************************************************************************************************
 */

namespace Inesonic\PurgeInactiveUsers;
    require_once dirname(__FILE__) . '/helpers.php';
    require_once dirname(__FILE__) . '/options.php';

    /**
     * Class that manages options displayed within the WordPress Plugins page.
     */
    class PlugInsPage {
        /**
         * Static method that is triggered when the plug-in is activated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_activated(Options $options) {}

        /**
         * Static method that is triggered when the plug-in is deactivated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_deactivated(Options $options) {}

        /**
         * Constructor
         *
         * \param[in] $plugin_basename    The base name for the plug-in.
         *
         * \param[in] $plugin_name        The user visible name for this plug-in.
         *
         * \param[in] $options            The plug-in options API.
         */
        public function __construct(
                string  $plugin_basename,
                string  $plugin_name,
                Options $options
            ) {
            $this->plugin_basename = $plugin_basename;
            $this->plugin_name = $plugin_name;
            $this->options = $options;

            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt the plug-in settings UI into WordPress.
         */
        public function on_initialization() {
            add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'add_plugin_page_links'));
            add_action(
                'after_plugin_row_' . $this->plugin_basename,
                array($this, 'add_plugin_configuration_fields'),
                10,
                3
            );

            add_action('wp_ajax_inesonic_piu_get_settings' , array($this, 'get_settings'));
            add_action('wp_ajax_inesonic_piu_update_settings' , array($this, 'update_settings'));
        }

        /**
         * Method that adds links to the plug-ins page for this plug-in.
         */
        public function add_plugin_page_links(array $links) {
            $configuration = "<a href=\"###\" id=\"inesonic-piu-configure-link\">" .
                               __("Configure", 'inesonic-piu') .
                             "</a>";
            array_unshift($links, $configuration);

            return $links;
        }

        /**
         * Method that adds links to the plug-ins page for this plug-in.
         */
        public function add_plugin_configuration_fields(string $plugin_file, array $plugin_data, string $status) {
            echo '<tr id="inesonic-piu-configuration-area-row"
                      class="inesonic-piu-configuration-area-row inesonic-row-hidden">
                    <th></th> .
                    <td class="inesonic-piu-configuration-area-column" colspan="3">
                      <table><tbody>
                        <tr>
                          <td>' . __("Inactive Time:", 'inesonic-piu') . '</td>
                          <td>
                            <input type="text"
                                   placeholder="Inactive Time (Days)"
                                   class="inesonic-piu-inactive-time-days"
                                   id="inesonic-piu-inactive-time-days"/>
                          </td>
                          <td>' . __("Days", 'inesonic-piu') . '</td>
                        </tr>
                        </tr>
                          <td class="inesonic-piu-inactive-roles-header">' .
                            __("Inactive Roles:", 'inesonic-piu') . '
                          </td>
                          <td colspan="2" class="inesonic-piu-inactive-roles-area">
                            <select class="inesonic-piu-inactive-roles-select"
                                    style="height: 200px;"
                                    id="inesonic-piu-inactive-roles-select"
                                    multiple
                            >
                            </select>
                            <br/>' .
                            __("(Select all that apply)", 'inesonic-piu') . '
                          </td>
                        </tr>
                        <tr>
                          <td colspan="3">
                            <div class="inesonic-piu-button-wrapper">
                              <a id="inesonic-piu-configure-submit-button"
                                 class="button action inesonic-piu-button-anchor"
                              >' .
                                __("Submit", 'inesonic-piu') . '
                              </a>
                            </div>
                          </td>
                        </tr>
                      </tbody></table>
                    </td>
                  </tr>';

            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-piu-plugins-page',
                \Inesonic\PurgeInactiveUsers\javascript_url('plugins-page'),
                array('jquery'),
                null,
                true
            );
            wp_localize_script(
                'inesonic-piu-plugins-page',
                'ajax_object',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );

            wp_enqueue_style(
                'inesonic-piu-styles',
                \Inesonic\PurgeInactiveUsers\css_url('inesonic-piu-styles'),
                array(),
                null
            );
        }

        /**
         * Method that is triggered to get the current Piu settings.
         */
        public function get_settings() {
            if (current_user_can('activate_plugins')) {
                $current_roles_data = get_editable_roles();
                $current_roles = array();
                foreach ($current_roles_data as $role_name => $role_data) {
                    $current_roles[$role_name] = $role_data['name'];
                }

                $response = array(
                    'status' => 'OK',
                    'inactive_time_days' => $this->options->inactive_time_days(),
                    'all_roles' => $current_roles,
                    'inactive_roles' => $this->options->inactive_user_roles()
                );
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered to update the Piu settings.
         */
        public function update_settings() {
            if (current_user_can('activate_plugins')           &&
                array_key_exists('inactive_time_days', $_POST) &&
                array_key_exists('inactive_roles', $_POST)        ) {
                $inactive_time_days = intval(sanitize_text_field($_POST['inactive_time_days']));

                $current_roles_data = get_editable_roles();
                $inactive_roles = array();
                foreach ($_POST['inactive_roles'] as $inactive_role) {
                    if (array_key_exists($inactive_role, $current_roles_data)) {
                        $inactive_roles[] = sanitize_text_field($inactive_role);
                    }
                }

                $this->options->set_inactive_time_days($inactive_time_days);
                $this->options->set_inactive_user_roles($inactive_roles);

                $response = array('status' => 'OK');
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }
    };
