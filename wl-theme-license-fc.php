<?php
/*
Plugin Name: Weblasser Client Management Plugin
Description: A theme licensing plugin for Weblasser
Version: 1.0
Author: Faheem Chowdhury
*/

// Register Custom Post Type
function employee_management_post_type() {
    $labels = array(
        'name'               => 'Theme Clients',
        'singular_name'      => 'Client',
        'menu_name'          => 'Theme Clients',
        'name_admin_bar'     => 'Client',
        'add_new'            => 'Add New Client',
        'add_new_item'       => 'Add New Client',
        'new_item'           => 'New Client',
        'edit_item'          => 'Edit Client',
        'view_item'          => 'View Client',
        'all_items'          => 'All Clients',
        'search_items'       => 'Search Clients',
        'parent_item_colon'  => 'Parent Clients:',
        'not_found'          => 'No Clients found.',
        'not_found_in_trash' => 'No Clients found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'employee' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor' ),
    );

    register_post_type( 'employee', $args );
}
add_action( 'init', 'employee_management_post_type' );

// function hide_view_link_for_employee_post_type($actions, $post) {
//     if ($post->post_type == 'employee') {
//         unset($actions['view']);
//     }
//     return $actions;
// }

// add_filter('post_row_actions', 'hide_view_link_for_employee_post_type', 10, 2);

function restrict_employee_posts() {
    // Check if it's a single post of the 'employee' post type
    if (is_singular('employee')) {
        
        // Add your condition here to check if the user should have access
        // For example, you can check if the user is logged in or has a specific role
        if (!is_user_logged_in() || !current_user_can('read_employee_posts')) {

            // Redirect the user to a custom page or the homepage
            wp_redirect(home_url());
            exit;
        }
    }
}

add_action('template_redirect', 'restrict_employee_posts');


function change_plugin_menu_icon() {
    global $menu;

    foreach ( $menu as $key => $menu_item ) {
        if ( 'Theme Clients' === $menu_item[0] ) {
            // Replace 'dashicons-star-filled' with the desired Dashicons class
            $menu[$key][6] = 'dashicons-lock';
            break;
        }
    }
}

add_action( 'admin_menu', 'change_plugin_menu_icon' );

// Add a submenu under "All Employees"
function add_license_generation_submenu() {
    add_submenu_page(
        'edit.php?post_type=employee', // Slug of the "All Employees" page
        'License Generation',
        'License Generation',
        'manage_options',
        'license-generation',
        'license_generation_page'
    );
}

add_action('admin_menu', 'add_license_generation_submenu');

// License Generation Page Content
function license_generation_page() {
    ?>
    <div class="wrap">
        <h2>License Generation</h2>
        <p>Click the button to generate a random license key.</p>
        <button id="generate-license-key-button" class="button">Generate License Key</button>
        <div id="license-key-result"></div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#generate-license-key-button').click(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'generate_license_key'
                    },
                    success: function(response) {
                        $('#license-key-result').html('<h3>Generated License Key: <span style="color:red">' + response + '</span></h3>');
                    }
                });
            });
        });
    </script>
    <?php
}
// AJAX callback to generate a license key
function generate_license_key_callback() {
    $license_key = generate_license_key();
    echo $license_key;
    die();
}

add_action('wp_ajax_generate_license_key', 'generate_license_key_callback');
add_action('wp_ajax_nopriv_generate_license_key', 'generate_license_key_callback');

// Function to generate a random license key
function generate_license_key() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $license_key = '';
    $length = 16;

    for ($i = 0; $i < $length; $i++) {
        $license_key .= $characters[rand(0, strlen($characters) - 1)];

        if ($i % 4 == 3 && $i < $length - 1) {
            $license_key .= '-';
        }
    }

    return $license_key;
}


// Add custom fields to the Employee post type
function employee_management_add_custom_fields() {
    add_meta_box( 'employee_details', 'Employee Details', 'employee_details_callback', 'employee', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'employee_management_add_custom_fields' );

function enqueue_datetimepicker() {
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-datepicker-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );

    // Enqueue jQuery UI DateTimePicker
    wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', array('jquery', 'jquery-ui-datepicker'), '1.6.3', true );
    wp_enqueue_style( 'jquery-ui-timepicker-style', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css' );
}

add_action( 'admin_enqueue_scripts', 'enqueue_datetimepicker' );



// Callback function to display custom fields
function employee_details_callback( $post ) {
    $employee_site_url = get_post_meta( $post->ID, '_employee_site_url', true );
    $license_key = get_post_meta( $post->ID, '_license_key', true );
    $expiration_date = get_post_meta( $post->ID, '_expiration_date', true );
    $status = get_post_meta( $post->ID, '_status', true );

    // Output employee data
    ?>
    <label for="employee_site_url">Client Site URL:</label>
    <input type="text" name="employee_site_url" value="<?php echo esc_attr( $employee_site_url ); ?>"><br>

    <label for="license_key">License Key:</label>
    <input type="text" name="license_key" value="<?php echo esc_attr( $license_key ); ?>"><br>

    <label for="expiration_date">Expiration Date and Time:</label>
    <input type="text" id="expiration_date" name="expiration_date" value="<?php echo esc_attr( $expiration_date ); ?>"><br>

    <label for="status">Status:</label>
    <select name="status">
        <option value="on" <?php selected( $status, 'on' ); ?>>On</option>
        <option value="off" <?php selected( $status, 'off' ); ?>>Off</option>
    </select>

    <script>
        jQuery(document).ready(function($) {
            $('#expiration_date').datetimepicker({
                dateFormat: 'yy-mm-dd',
                timeFormat: 'HH:mm:ss', // Customize the time format as needed
                changeMonth: true,
                changeYear: true
            });
        });
    </script>
    <?php
}



// Save custom fields data
function employee_management_save_custom_fields( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['employee_site_url'] ) ) {
        update_post_meta( $post_id, '_employee_site_url', sanitize_text_field( $_POST['employee_site_url'] ) );
    }

    if ( isset( $_POST['license_key'] ) ) {
        update_post_meta( $post_id, '_license_key', sanitize_text_field( $_POST['license_key'] ) );
    }

    if ( isset( $_POST['expiration_date'] ) ) {
        update_post_meta( $post_id, '_expiration_date', sanitize_text_field( $_POST['expiration_date'] ) );
    }

    if ( isset( $_POST['status'] ) ) {
        update_post_meta( $post_id, '_status', sanitize_text_field( $_POST['status'] ) );
    }
}
add_action( 'save_post', 'employee_management_save_custom_fields' );


// Add custom columns to the employee list
function employee_management_columns( $columns ) {
    $columns['employee_site_url'] = 'Client Site URL';
    $columns['license_key'] = 'License Key';
    $columns['expiration_date'] = 'Expiration Date';
    $columns['status'] = 'Status';
    $columns['days_until_expiration'] = 'Days Until Expiration'; // New column
    return $columns;
}
add_filter( 'manage_employee_posts_columns', 'employee_management_columns' );


// Populate custom columns with data
function employee_management_custom_column( $column, $post_id ) {
    switch ( $column ) {
        case 'employee_site_url':
            echo esc_html( get_post_meta( $post_id, '_employee_site_url', true ) );
            break;
        case 'license_key':
            echo esc_html( get_post_meta( $post_id, '_license_key', true ) );
            break;
        case 'expiration_date':
            echo esc_html( get_post_meta( $post_id, '_expiration_date', true ) );
            break;
        case 'status':
            echo esc_html( get_post_meta( $post_id, '_status', true ) );
            break;
            case 'days_until_expiration':
                $expiration_date = get_post_meta( $post_id, '_expiration_date', true );
                if ( $expiration_date ) {
                    $current_time = current_time( 'timestamp' );
                    $expiration_time = strtotime( $expiration_date );
                    $days_until_expiration = ceil( ( $expiration_time - $current_time ) / ( 60 * 60 * 24 ) );
    
                    if ($days_until_expiration == 1) {
                        echo esc_html( $days_until_expiration ) . ' Day';
                    } else {
                        echo esc_html( $days_until_expiration ) . ' Days';
                    }
                } else {
                    echo 'N/A';
                }
                break;
    }
}
add_action( 'manage_employee_posts_custom_column', 'employee_management_custom_column', 10, 2 );


// Make custom columns sortable
function employee_management_sortable_columns( $columns ) {
    $columns['employee_site_url'] = 'employee_site_url';
    $columns['license_key'] = 'license_key';
    $columns['expiration_date'] = 'expiration_date';
    $columns['status'] = 'status';
    $columns['days_until_expiration'] = 'days_until_expiration';

    
    return $columns;
}
add_filter( 'manage_edit-employee_sortable_columns', 'employee_management_sortable_columns' );

// Add quick edit fields to the employee list
function employee_management_quick_edit_custom_box( $column_name, $post_type ) {
    if ( 'status' === $column_name && 'employee' === $post_type ) {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title">Status</span>
                    <select name="_status">
                        <option value="on">On</option>
                        <option value="off">Off</option>
                    </select>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action( 'quick_edit_custom_box', 'employee_management_quick_edit_custom_box', 10, 2 );



function send_employee_data_to_remote_site( $ID, $post ) {
    if ( 'employee' === $post->post_type ) {
        $employee_site_url = get_post_meta( $ID, '_employee_site_url', true );
        $license_key = get_post_meta( $ID, '_license_key', true );
        $expiration_date = get_post_meta( $ID, '_expiration_date', true );
        $status = get_post_meta( $ID, '_status', true );

        // Prepare the data to send
        $data = array(
			'employee_site_url' => $employee_site_url,
            'license_key' => $license_key,
            'expiration_date' => $expiration_date,
            'status' => $status,
        );

        // Send data to the remote site using the REST API
        $remote_site_url = esc_url( $employee_site_url );
        $response = wp_safe_remote_post( $remote_site_url . '/wp-json/weblasser/v1/save-theme-clients-data/', array(
            'body' => json_encode( $data ),
            'headers' => array( 'Content-Type' => 'application/json' ),
        ) );

        // Handle the response as needed
        if ( ! is_wp_error( $response ) && $response['response']['code'] === 200 ) {
            // Data sent successfully
            $response_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( isset( $response_data->message ) ) {
                // Optionally, you can log or display a success message
            }
        } else {
            // Error occurred during data transfer
            // Handle errors here
        }
    }
}

add_action( 'save_post', 'send_employee_data_to_remote_site', 10, 2 );






