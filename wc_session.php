<?php

// get session for woocommerce platform
$wp_load_file = dirname(dirname(__FILE__)).'/wp-load.php';

if (file_exists($wp_load_file)) {
    include_once $wp_load_file;

    if (is_user_logged_in()) {

        global $current_user;
        get_currentuserinfo();

        if (isset($current_user->data) && isset($current_user->data->ID)) {
            $_SESSION['is_logged'] = array(
              'login'     => true,
              'email'     => $current_user->data->user_email,
              'id'        => $current_user->data->ID,
              'is_logged' => true,
            );
        }
    }
}
