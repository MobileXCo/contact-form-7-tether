<?php
/*
Plugin Name: Contact Form 7 - Tether Extension
Plugin URI: https://tetherxmp.com/
Description: Tether integration
Author: Adam Goucher / MobileXCo
Version: 1.0
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (!function_exists('wp_get_current_user')) {
    require_once ABSPATH . WPINC . '/pluggable.php';
}

register_activation_hook(   __FILE__, "cf7thr_activate" );
register_deactivation_hook( __FILE__, "cf7thr_deactivate" );
register_uninstall_hook(    __FILE__, "cf7thr_uninstall" );

function cf7thr_activate() {
    $cf7thr_options = [
        'identifier'                    => 'mobile',
        'client'                        => '',
        'tether_endpoint'               => 'https://tetherxmp.com',
        'tether_oauth_client_id'        => '',
        'tether_oauth_client_secret'    => '',
        'tether_oauth_username'         => '',
        'tether_oauth_password'         => ''
    ];

    add_option("cf7thr_options", $cf7thr_options);
}

function cf7thr_deactivate() { }

function cf7thr_uninstall() {
    remove_option("cf7thr_options");
}

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
    function cf7thr_admin_menu() {
        $addnew = add_submenu_page(
            'wpcf7',
            __( 'Tether Settings', 'contact-form-7' ),
            __( 'Tether Settings', 'contact-form-7' ),
            'wpcf7_edit_contact_forms',
            'cf7thr_admin_form',
            'cf7thr_admin_form'
        );
    }
    add_action('admin_menu', 'cf7thr_admin_menu', 20);
    add_action('wpcf7_before_send_mail', 'cf7thr_before_send_mail');
    add_action('wpcf7_admin_after_additional_settings', 'cf7thr_admin_after_additional_settings');
    add_filter('wpcf7_editor_panels', 'cf7thr_editor_panels' );
    add_action('wpcf7_after_save', 'cf7thr_save_contact_form');
}

function cf7thr_admin_form() {
    if (!current_user_can( "manage_options" )) {
        wp_die(__("You do not have sufficient permissions to access this page."));
    }

    ?>

    <form method='post'>

    <?php
    if (isset($_POST['update'])) {
        $options['identifier'] = sanitize_text_field($_POST['identifier']);
        $options['client'] = sanitize_text_field($_POST['client']);
        $options['tether_endpoint'] = sanitize_text_field($_POST['tether_endpoint']);
        $options['tether_oauth_client_id'] = sanitize_text_field($_POST['tether_oauth_client_id']);
        $options['tether_oauth_client_secret'] = sanitize_text_field($_POST['tether_oauth_client_secret']);
        $options['tether_oauth_username'] = sanitize_text_field($_POST['tether_oauth_username']);
        $options['tether_oauth_password'] = sanitize_text_field($_POST['tether_oauth_password']);

        update_option("cf7thr_options", $options);

        echo "<br /><div class='updated'><p><strong>"; _e("Settings Updated."); echo "</strong></p></div>";
    }


    $options = get_option('cf7thr_options');
    foreach ($options as $k => $v ) {
        $value[$k] = $v;
    }

    $siteurl = get_site_url();

    ?>

    <table width='70%'>
        <tr>
            <td>
                <div class='wrap'>
                    <h2>Contact Form 7 - Tether Settings</h2>
                </div>
                <br />
            </td>
            <td>
                <input type='submit' name='btn2' class='button-primary' style='font-size: 17px;line-height: 28px;height: 32px;float: right;' value='Save Settings'>
            </td>
        </tr>
    </table>

    <table width='100%'>
        <tr>
            <td width='70%'>
                <div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
        &nbsp; Client Settings
                </div>
                <div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;">
                    <b>Client ID</b>
                    <input type='text' name='client' value='<?php echo $value['client']; ?>'>
                    <br />
                    Enter your Client ID from Tether here.
<!--
                    <br />
                    <br />
                    <b>Identifier</b>
                    <select name="identifier">
                        <option <?php if ($value['identifier'] == "Mobile") { echo "SELECTED"; } ?> value="Mobile">Mobile</option>
                        <option <?php if ($value['identifier'] == "Email") { echo "SELECTED"; } ?> value="Email">Mobile</option>
                    </select>
                    <br />
                    What are you using as your identifier within Tether.
 -->
                </div>
            </td>
        </tr>
    </table>

    <table width='100%'>
        <tr>
            <td width='70%'>
                <div style="background-color:#333333;padding:8px;color:#eee;font-size:12pt;font-weight:bold;">
        &nbsp; OAuth2 Settings
                </div>
                <div style="background-color:#fff;border: 1px solid #E5E5E5;padding:5px;">
                    <b>OAuth Client ID</b>
                    <input type='text' name='tether_oauth_client_id' value='<?php echo $value['tether_oauth_client_id']; ?>'>
                    <br />
                    Enter your Client ID from Tether here.
                    <br />
                    <br />
                    <b>OAuth Client Secret</b>
                    <input type='password' name='tether_oauth_client_secret' value='<?php echo $value['tether_oauth_client_secret']; ?>'>
                    <br />
                    Enter your Client ID from Tether here.
                    <br />
                    <br />
                    <b>OAuth Client Username</b>
                    <input type='text' name='tether_oauth_username' value='<?php echo $value['tether_oauth_username']; ?>'>
                    <br />
                    Enter your Client ID from Tether here.
                    <br />
                    <br />
                    <b>OAuth Client Password</b>
                    <input type='password' name='tether_oauth_password' value='<?php echo $value['tether_oauth_password']; ?>'>
                    <br />
                    Enter your Client ID from Tether here.
                    <br />
                </div>
            </td>
        </tr>
    </table>
    <input type='hidden' name='update' value='1'>
    <input type='hidden' name='tether_endpoint' value='https://tetherxmp.com'>
    <input type='hidden' name='identifier' value='Mobile'>

    <?php
}



class TetherApiHttpException extends Exception {}

if (!function_exists('make_request')) {
    /**
     * Simplified method to make requests with cURL
     *
     * @param string $method    HTTP method: POST, GET, PUT, PATCH, DELETE
     * @param string $url       URL to make request to
     * @param array  $data      Data to pass with request
     * @param array  $headers   Headers to set on the request
     *
     * @return array
     * @throws TetherApiHttpException
     */
    function make_request($method, $url, $data = [], $headers = []) {
        $method = strtoupper($method);
        $url = filter_var($url, FILTER_VALIDATE_URL);

        // Default options for cURL
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_HEADER => true,
            // CURLOPT_SSL_VERIFYPEER => false,
            // CURLOPT_SSL_VERIFYHOST => false,
        ];

        // Check for valid HTTP method
        if (! in_array($method, ['POST', 'GET', 'PUT', 'PATCH', 'DELETE'])) {
            throw new Exception("Invalid request method: '$method'");
        }

        // Check for valid url
        if (! $url) {
            throw new Exception("Invalid url: '$url'");
        }

        // if ($method == 'POST') {
        //     $options[CURLOPT_POST] = true;
        // }

        // Set custom request method
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        // Build get parameters and query
        if ($method == 'GET') {
            $url = rtrim($url, '/') . '?' . http_build_query($data);
        } else {
            $options[CURLOPT_POSTFIELDS] = $data;
        }

        if (! empty($headers)) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        $response_headers = [];

        // Set the request url
        $options[CURLOPT_URL] = $url;

        // Set header retrieval function
        $options[CURLOPT_HEADERFUNCTION] = function($curl, $header) use (&$response_headers) {
            $len = strlen($header);
            $header = explode(':', $header, 2);

            // ignore invalid headers
            if (count($header) < 2) {
                return $len;
            }

            $name = strtolower(trim($header[0]));

            if (!array_key_exists($name, $response_headers)) {
                $response_headers[$name] = [trim($header[1])];
            } else {
                $response_headers[$name][] = trim($header[1]);
            }

            return $len;
        };

        // Initialize cURL session
        $ch = curl_init();

        // Set request cURL options
        curl_setopt_array($ch, $options);

        // Execute cURL request
        $response_body = curl_exec($ch);

        // Fetch response HTTP code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code < 200 || $http_code > 299) {
            // Request unsuccessful, log errors and throw exception
            $message = "CF7 Tether: HTTP code $http_code: "
                . curl_error($ch) . PHP_EOL
                . json_encode($response_headers) . PHP_EOL
                . $response_body;
            curl_close($ch);        // Close session
            error_log($message);    // Log error
            throw new TetherApiHttpException($message); // Throw exception
        }

        // Close cURL session
        curl_close($ch);

        // Request succcess, return array of response body
        return json_decode($response_body, true);
    }
}

if (!function_exists('make_request_with_token')) {
    /**
     * Simplified method to make requests with cURL with OAuth access token
     *
     * @param string $method    HTTP method: POST, GET, PUT, PATCH, DELETE
     * @param string $url       URL to make request to
     * @param array  $data      Data to pass with request
     * @param array  $headers   Headers to set on the request
     *
     * @return array
     * @throws TetherApiHttpException
     */
    function make_request_with_token($method, $url, $data = [], $headers = []) {
        $response_body = null;
        // Attempt to fetch an access token
        try {
            $config = get_option('cf7thr_options');

            if (empty($config['tether_endpoint'])
                || empty($config['tether_oauth_client_id'])
                || empty($config['tether_oauth_client_secret'])
                || empty($config['tether_oauth_username'])
                || empty($config['tether_oauth_password'])) {
                throw new Exception('CF7 Tether: Missing configuration values for authentication with service');
            }

            $response_body = make_request(
                'POST',
                rtrim($config['tether_endpoint'], '/') . '/oauth/token',
                [
                    'grant_type' => 'password',
                    'client_id' => $config['tether_oauth_client_id'],
                    'client_secret' => $config['tether_oauth_client_secret'],
                    'username' => $config['tether_oauth_username'],
                    'password' => $config['tether_oauth_password']
                ]
            );
        } catch  (TetherApiHttpException $e) {
            error_log('CF7 Tether: Failed to fetch access token');
            throw new TetherApiHttpException('CF7 Tether: Failed to fetch access token');
        } catch (Exception $e) {
            error_log('CF7 Tether: Failed to fetch access token');
            error_log('CF7 Tether: ' . $e->getMessage());
            throw new $e;
        }

        // Verify there's an access token in the response
        if (empty($response_body['access_token'])) {
            error_log('CF7 Tether: Failed to fetch access token');
            throw new TetherApiHttpException('CF7 Tether: Failed to fetch access token');
        }

        // Merge OAuth headers
        $headers = array_merge(
            $headers,
            [
                'Authorization: Bearer ' . $response_body['access_token'],
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        );

        // Make request with OAuth headers
        return make_request($method, $url, $data, $headers);
    }
}

if (!function_exists('api_call')) {
    /**
     * Simplified method to make requests with cURL with OAuth access token
     * and appropriate json headers
     *
     * @param string $method    HTTP method: POST, GET, PUT, PATCH, DELETE
     * @param string $url       URL to make request to
     * @param array  $data      Data to pass with request
     * @param array  $headers   Headers to set on the request
     *
     * @return array
     * @throws TetherApiHttpException
     */
    function api_call($method, $url, $data = [], $headers = []) {
        // Merge headers
        $headers = array_merge(
            $headers,
            [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        );

        // Convert data to JSON
        $data_string = json_encode($data);

        // Remove content-length
        if (in_array('Content-Length', $headers)) {
            unset($headers[array_search('Content-Length', $headers)]);
        }

        // Set content-length
        $headers[] = 'Content-Length: ' . strlen($data_string);

        // Make request with token
        return make_request_with_token(
            $method,
            $url,
            $data_string,
            $headers
        );
    }
}

function cf7thr_before_send_mail($cf7) {
    $form_id = $cf7->id();

    $config = get_option('cf7thr_options');

    // create participant
    $submission = WPCF7_Submission::get_instance();
    $data = $submission->get_posted_data();

    $mappings = get_post_meta($form_id, '_cf7thr_mappings', true);
    ksort($mappings);   // Ensure the keys are in the same order
    ksort($data);       // Ensure the keys are in the same order
    $parsedData = array_combine(
        $mappings,                              // Remap keys to values
        array_intersect_key($data, $mappings)   // Keep only similar array keys
    );

    $identifiers = [];

    // Phone is mapped
    if (! empty($parsedData['phone'])) {
        $identifiers[] = [
            'type' => 'phone',
            'value' => $parsedData['phone']
        ];

    }
    // Email is mapped
    elseif (! empty($parsedData['email'])) {
        $identifiers[] = [
            'type' => 'email',
            'value' => $parsedData['email']
        ];
    }
    // No identifiers present, log error
    else {
        error_log('CF7 Tether: No identifier provided.');
    }

    if (! empty($identifiers)) {
        $actual_data = [];
        foreach($data as $key => $keyvalue) {
            if (mb_substr($key, 0, 1, 'utf-8') != '_') {
                if (array_key_exists($key, $mappings)) {
                    $actual_data[$mappings[$key]] = $keyvalue;
                } else {
                    $actual_data[$key] = $keyvalue;
                }
            }
        }

        // Remove unwanted data
        unset($actual_data['referer-page']);

        $response_body = api_call(
            'POST',
            rtrim($config['tether_endpoint'], '/') . '/api/v2/participants',
            [
                'identifiers' => $identifiers,
                'client' => $config['client'],
                'data' => $actual_data,
            ]
        );

        $participant_id = $response_body['participant'];

        if (! empty($parsedData['message']) && ! empty($parsedData['email'])) {
            $message_response_body = api_call(
                'POST',
                rtrim($config['tether_endpoint'], '/') . '/internal/messages',
                [
                    'client' => $config['client'],
                    'messages' => [
                        [
                            'participant' => [
                                'identifier' => $parsedData['email'],
                                'type' => 'email',
                                'metadata' => $actual_data,
                            ],
                            'channel' => [
                                // 'identifier' => 'something-here',
                                'type' => 'cf7',
                            ],
                            'message' => [
                                // 'external_identifier' => 'something-here',
                                'content' => $parsedData['message'],
                                'timestamp' => gmdate('Y-m-d g:i:s'),
                            ],
                        ]
                    ]
                ]
            );
        }

        if ($data['tether-lists']) {
            error_log('CF7 Tether: tether-lists is: ' . $data['tether-lists']);
            error_log('CF7 Tether: adding to a list');

            $response_body = api_call(
                'GET',
                rtrim($config['tether_endpoint'], '/') . '/api/v2/lists',
                [
                    'client' => $config['client'],
                    'name' => $data['tether-lists']
                ]
            );

            $list_id = null;

            if (! empty($response_body['data'])
                && ! empty($response_body['data'][0])
                && ! empty($response_body['data'][0]['id'])) {
                $list_id = $response_body['data'][0]['id'];
            }

            // assign
            if ($list_id) {
                $response_body = null;
                try {
                    $response_body = api_call(
                        'PUT',
                        rtrim($config['tether_endpoint'], '/') . '/api/v2/lists/' . $list_id,
                        [
                            'client' => $config['client'],
                            'participants' => [$participant_id],
                            'action' => 'add'
                        ]
                    );
                } catch (Exception $e) {
                    error_log("CF7 Tether: Could not add participant '$participant_id' to list '$list_id'");
                }
            }
        } else {
            error_log('CF7 Tether: no tether-lists to add to');
        }
    }
}

function cf7thr_admin_after_additional_settings($cf7) {
    $post_id = $cf7->id();

    $enable = get_post_meta($post_id, "_cf7thr_enable", true);
    $mappings = get_post_meta($post_id, "_cf7thr_mappings", true);

    if ($enable == "1") {
        $checked = "CHECKED";
    } else {
        $checked = "";
    }

    if ($mappings) {
        $counter = 1;
        $mapping_fields = '';
        foreach ($mappings as $key => $value) {
            $mapping_fields = $mapping_fields . '<tr><td><input name="cf7thr_mapping_cf7_' . $counter . '" type="text" id="cf7thr_mapping_cf7_' . $counter . '" value="' . $key . '"></input></td>';
            $mapping_fields = $mapping_fields . '<td><input name="cf7thr_mapping_thr_' . $counter . '" type="text" id="cf7thr_mapping_thr_' . $counter . '" value="' . $value . '"></input></td>';
            $mapping_fields = $mapping_fields . '<td><input type="button" class="cf7thr_mapping_delete" value="Delete"></input></td></tr>';
            $counter++;
        }
    } else {
        $mapping_fields = "";
    }

    $mappings = <<<MAPPINGS
<div id='tether-settings-container' class='meta-box-sortables ui-sortable'>
    <div id='tether-settings' class='postbox'>
        <h3 class='hndle ui-sortable-handle'>
            <span>Tether Settings</span>
        </h3>
        <div class='inside'>
            <input name='cf7thr_enable' value='1' type='checkbox' $checked>
            <label>Enable Tether on this form</label>
            <br />
            <br />
            <div>Participant Field Mappings:</div>
            <table width="100%" id="cf7thr_mappings">
                <thead>
                    <tr>
                        <td width="50%">CF7 Field</td>
                        <td>Participant Field</td>
                    </tr>
                </thead>
                <tbody>
                    $mapping_fields
                </tbody>
            </table>
            <input type="button" id="addMappingButton" value="Add Mapping"/>
        </div>
    </div>
</div>
<input type='hidden' name='cf7thr_post_settings' value='$post_id'>

<script>
jQuery("#addMappingButton" ).bind( "click", function(e) {
    var how_many = jQuery('#cf7thr_mappings tbody').children().length
    how_many++;
    new_row = '<tr><td><input name="cf7thr_mapping_cf7_' + how_many + '" type="text" id="cf7thr_mapping_cf7_' + how_many + '"></input></td>';
    new_row = new_row + '<td><input name="cf7thr_mapping_thr_' + how_many + '" type="text" id="cf7thr_mapping_thr_' + how_many + '"></input></td>';
    new_row = new_row + '<td><input type="button" class="cf7thr_mapping_delete" value="Delete"></input></td></tr>';
    jQuery('#cf7thr_mappings tbody').append(new_row);
});

jQuery('.cf7thr_mapping_delete').live("click", function(e) {
    jQuery(e.target).parent().parent().remove();
});
</script>
MAPPINGS;

    echo $mappings;
}

function cf7thr_editor_panels($panels) {
    $new_page = [
        'Tether' => [
            'title' => __( 'Tether', 'contact-form-7' ),
            'callback' => 'cf7thr_admin_after_additional_settings'
        ]
    ];

    $panels = array_merge($panels, $new_page);

    return $panels;
}

function array_filter_key( $input, $callback ) {
    if ( !is_array( $input ) ) {
        trigger_error( 'array_filter_key() expects parameter 1 to be array, ' . gettype( $input ) . ' given', E_USER_WARNING );
        return null;
    }

    if ( empty( $input ) ) {
        return $input;
    }

    $filteredKeys = array_filter( array_keys( $input ), $callback );
    if ( empty( $filteredKeys ) ) {
        return [];
    }

    $input = array_intersect_key( array_flip( $filteredKeys ), $input );

    return $input;
}


function cf7thr_save_contact_form($cf7) {
    $post_id = sanitize_text_field($_POST['cf7thr_post_settings']);

    if (!empty($_POST['cf7thr_enable'])) {
        $enable = sanitize_text_field($_POST['cf7thr_enable']);
        update_post_meta($post_id, "_cf7thr_enable", $enable);
    } else {
        update_post_meta($post_id, "_cf7thr_enable", 0);
    }

    $keys = array_filter_key($_POST, function($key) {
        return strpos($key, 'cf7thr_mapping_cf7_') === 0;
    });

    if ($keys) {
        $mappings = [];
        foreach (array_keys($keys) as $key) {
            $parts = explode('_', $key);
            $mappings[sanitize_text_field($_POST[$key])] = sanitize_text_field($_POST[$parts[0] . '_' . $parts[1] . '_thr_' . $parts[3]]);
        }
        update_post_meta($post_id, "_cf7thr_mappings", $mappings);
    }
}

