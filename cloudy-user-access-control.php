<?php
/**
 * Plugin Name: Cloudy User Access Control
 * Description: Dead simple plugin that allows admins to disable user access while preserving their content
 * Version: 1.0
 * Author: CloudyTechnologies
 * Author URI: https://cloudytechnologies.mk/
 * Plugin URI: https://github.com/cloudy-Technologies/cloudy-user-access-control
 */

if (!defined('ABSPATH')) {
    exit;
}

function uac_add_user_meta($user_id) {
    add_user_meta($user_id, 'account_status', 'active', true);
}

register_activation_hook(__FILE__, 'uac_add_user_meta');

function uac_admin_menu() {
    add_users_page(
        'User Access Control',
        'Access Control',
        'edit_users',
        'user-access-control',
        'uac_admin_page'
    );
}

add_action('admin_menu', 'uac_admin_menu');

function uac_admin_page() {
    if (!current_user_can('edit_users')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['update_status']) && isset($_POST['user_id']) && isset($_POST['status'])) {
        $user_id = intval($_POST['user_id']);
        $status = sanitize_text_field($_POST['status']);
        update_user_meta($user_id, 'account_status', $status);
    }

    $users = get_users();

    ?>

    <div class="wrap">
        <h1>Cloudy User Access Control</h1>
	      <p>Here you can disable user access while preserving their content.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): 
                    $current_status = get_user_meta($user->ID, 'account_status', true);
                ?>
                    <tr>
                        <td><?php echo esc_html($user->user_login); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                <select name="status">
                                    <option value="active" <?php selected($current_status, 'active'); ?>>Active</option>
                                    <option value="inactive" <?php selected($current_status, 'inactive'); ?>>Inactive</option>
                                </select>
                                <input type="submit" name="update_status" class="button" value="Update">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function uac_authenticate($user, $username, $password) {
    if (is_wp_error($user)) {
        return $user;
    }

    $status = get_user_meta($user->ID, 'account_status', true);
    
    if ($status === 'inactive') {
        return new WP_Error(
            'inactive_account',
            __('Cannot log in - inactive account.')
        );
    }

    return $user;
}

add_filter('authenticate', 'uac_authenticate', 30, 3);
