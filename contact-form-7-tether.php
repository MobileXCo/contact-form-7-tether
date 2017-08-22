<?php
/*
Plugin Name: Contact Form 7 - Tether Extension
Plugin URI: https://tetherxmp.com/
Description: Tether integration
Author: Adam Goucher
Author URI: https://tetherxmp.com/
Version: 0.6
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if (!function_exists('wp_get_current_user')) {
    require_once ABSPATH . WPINC . '/pluggable.php';
}

register_activation_hook(   __FILE__, "cf7thr_activate" );
register_deactivation_hook( __FILE__, "cf7thr_deactivate" );
register_uninstall_hook(    __FILE__, "cf7thr_uninstall" );

function cf7thr_activate() {
    $cf7thr_options = array(
        'identifier'                    => 'mobile',
        'client'                        => '',
        'tether_endpoint'               => 'https://tetherxmp.com',
        'tether_oauth_client_id'        => '',
        'tether_oauth_client_secret'    => '',
        'tether_oauth_username'         => '',
        'tether_oauth_password'         => ''
    );

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

function cf7thr_before_send_mail($cf7) {
    $form_id = $cf7->id();

    $options = get_option('cf7thr_options');
    foreach ($options as $k => $v ) {
        $value[$k] = $v;
    }

    // authenticate for token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $value['tether_endpoint'] . '/oauth/token');
    // curl_setopt($ch, CURLOPT_URL, 'https://tetherxmp.com/oauth/token');
    curl_setopt($ch, CURLOPT_POST, 1);

    $params = [
        'grant_type' => 'password',
        'client_id' => $value['tether_oauth_client_id'],
        'client_secret' => $value['tether_oauth_client_secret'],
        'username' => $value['tether_oauth_username'],
        'password' => $value['tether_oauth_password']
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response_body = curl_exec($ch);
    $info = curl_getinfo($ch);
    if ($info['http_code'] == 200) {
        $response = json_decode($response_body, true);
        $token = $response['access_token'];
        curl_close($ch);

        // create participant
        $submission = WPCF7_Submission::get_instance();
        $data = $submission->get_posted_data();

        $mappings = get_post_meta($form_id, '_cf7thr_mappings', true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $value['tether_endpoint'] . '/api/v2/participants');
        // curl_setopt($ch, CURLOPT_URL, 'https://tetherxmp.com/api/v2/participant');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'Content-Type: application/json'
        ));

        $identifiers = [];

        // phone is mapped
        if (array_search('phone', $mappings) && $data[array_search('phone', $mappings)]) {
            error_log('a');
            if (array_key_exists(array_search('phone', $mappings), $data)) {
                array_push($identifiers, 
                    [
                        'type' => 'phone',
                        'value' => $data[array_search('phone', $mappings)]
                    ]
                );
            }
        // phone is not mapped but exists
        } elseif (array_key_exists('phone', $data) && $data[array_search('phone', $mappings)]) {
            error_log('b');
            array_push($identifiers, 
                [
                    'type' => 'phone',
                    'value' => $data['phone']
                ]
            );
        // email is mapped
        } elseif (array_search('email', $mappings)) {
            if (array_key_exists(array_search('email', $mappings), $data) && $data[array_search('email', $mappings)]) {
                array_push($identifiers, 
                    [
                        'type' => 'email',
                        'value' => $data[array_search('email', $mappings)]
                    ]
                );
            }
        // email is not mapped but exists
        } elseif (array_key_exists('email', $data) && $data[array_search('email', $mappings)]) {
            error_log('d');
            array_push($identifiers, 
                [
                    'type' => 'email',
                    'value' => $data['email']
                ]
            );
        } else {
            error_log("TETHER: No identifier provided.");
        }

        $params = [
            'identifiers' => $identifiers,
            'client' => $value['client'],
        ];

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
        $extras = ['referer-page'];
        foreach ($extras as $key) {
            unset($actual_data[$key]);
        }
        $params['data'] = $actual_data;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // curl_setopt($ch, CURLOPT_VERBOSE, true);
        // $verbose = fopen('php://temp', 'w+');
        // curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] != 200) {
            error_log('TETHER: Could not create/update Participant');
        }

        // rewind($verbose);
        // $verboseLog = stream_get_contents($verbose);
        // error_log($verboseLog);

        curl_close($ch);

        $participant_id = json_decode($response_body, true)['participant'];

        if ($data['tether-lists']) {
            error_log('TETHER: tether-lists is: ' . $data['tether-lists']);    
        } else {
            error_log('TETHER: no tether-lists to add to');
        }
        

        if ($data['tether-lists']) {
            error_log('TETHER: adding to a list');
            // get list id
            $ch = curl_init();

            $encoded_bits =  'client=' . $value['client'] . '&name=' . curl_escape($ch, $data['tether-lists']);
            curl_setopt($ch, CURLOPT_URL, $value['tether_endpoint'] . '/api/v2/lists?' . $encoded_bits);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $token
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response_body = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            if ($info['http_code'] != 200) {
                error_log('TETHER: Could not find list "' . $params['name'] . '"');
            }

            $list_id = json_decode($response_body, true)['data'][0]['id'];
            
            // assign
            if ($list_id) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,
                    $value['tether_endpoint'] . 
                    '/api/v2/lists/' . $list_id);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: Bearer ' . $token,
                    'Content-type: application/json',
                ));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $params = [
                    'client' => $value['client'],
                    'participants' => [$participant_id],
                    'action' => 'add'
                ];
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

                // curl_setopt($ch, CURLOPT_VERBOSE, true);
                // $verbose = fopen('php://temp', 'w+');
                // curl_setopt($ch, CURLOPT_STDERR, $verbose);

                $response_body = curl_exec($ch);

                // rewind($verbose);
                // $verboseLog = stream_get_contents($verbose);
                // error_log($verboseLog);

                $info = curl_getinfo($ch);
                curl_close($ch);

                if ($info['http_code'] != 200) {
                    error_log('TETHER: Could not add participant "' . $participant_id . '" to list "' . $list_id . '"');
                }
            }

        }
    } else {
        error_log('TETHER: Could not authenticate.');
        curl_close($ch);
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
    $new_page = array(
        'Tether' => array(
            'title' => __( 'Tether', 'contact-form-7' ),
            'callback' => 'cf7thr_admin_after_additional_settings'
        )
    );
    
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
        return array();
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

