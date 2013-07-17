<?php
/*
 * Registers, Renders and Validates all Options.
 */

/*
 * Register the form setting for our kebo_options array.
 */

function kebo_twitter_options_init() {

    register_setting(
            'kebo_twitter_options', // Options group
            'kebo_twitter_options', // Database option
            'kebo_twitter_options_validate' // The sanitization callback,
    );

    add_settings_section(
            'kebo_twitter_options_general', // Unique identifier for the settings section
            __('Service Connection', 'kebo'), // Section title
            'kebo_twitter_connection_render', // Section callback
            'kebo-twitter' // Menu slug
    );

    /*
     * Use Settings Section Callback to Display Custom Twitter Connection HTML.
     */
    function kebo_twitter_connection_render() {
        ?>

        <p><?php _e("To enable us to read your Twitter Feed you must connect your Twitter account to our Twitter Application by clicking on the large 'Connect to Twitter' button below.", 'kebo_twitter'); ?></p>

        <?php if (false === ( $twitter_data = get_transient('kebo_twitter_connection') )) : ?>

            <a class="social-link twitter disabled" href="http://auth.kebopowered.com/twitterread/?origin=<?php echo admin_url('admin.php?page=kebo-twitter') ?>"><?php _e('Connect to Twitter', 'kebo_twitter'); ?></a>

        <?php else : ?>

            <a class="social-link twitter" href="#"><i class="icon-twitter"></i><?php echo __('Connected to Twitter', 'kebo_twitter'); ?></a><br>
            <p><?php _e('Connected as', 'kebo_twitter'); ?> <a class="account" href="<?php echo $twitter_data['account_link']; ?>" target="_blank">@<?php echo $twitter_data['account']; ?></a> <a class="disconnect" title="Disconnect Service" href="<?php echo admin_url('admin.php?page=kebo-twitter&reset=true') ?>">&#10006;</a></p>

        <?php endif; ?>

        <h3>General Options</h3>

        <?php
    }

    add_settings_field(
            'kebo_twitter_cache_timer', // Unique identifier for the field for this section
            __('Feed Refresh Rate', 'kebo_twitter'), // Setting field label
            'kebo_twitter_cache_timer_render', // Function that renders the settings field
            'kebo-twitter', // Menu slug
            'kebo_twitter_options_general' // Settings section.
    );
}
add_action('admin_init', 'kebo_twitter_options_init');

/**
 * Change the capability required to save the 'kebo_twitter_options' options group.
 */
function kebo_twitter_option_capability($capability) {

    return 'manage_options';
    
}
add_filter('option_page_capability_kebo_twitter_options', 'kebo_twitter_option_capability');

/**
 * Returns the options array for the 'kebo_twitter_options' option group.
 */
function kebo_get_twitter_options() {

    $saved = (array) get_option('kebo_twitter_options');

    $defaults = array(
        'kebo_twitter_cache_timer' => 15,
    );

    $defaults = apply_filters('kebo_get_twitter_options', $defaults);

    $options = wp_parse_args($saved, $defaults);
    $options = array_intersect_key($options, $defaults);

    return $options;
}

/**
 * Returns an array of radio options.
 */
function kebo_twitter_radio_buttons() {

    $kebo_twitter_radio_buttons = array(
        'yes' => array(
            'value' => 'yes',
            'label' => __('On', 'kebo_twitter')
        ),
        'no' => array(
            'value' => 'no',
            'label' => __('Off', 'kebo_twitter')
        ),
    );

    return apply_filters('kebo_twitter_radio_button', $kebo_twitter_radio_buttons);
}

/**
 * Renders the Cache Timer input.
 */
function kebo_twitter_cache_timer_render() {

    $options = kebo_get_twitter_options();
    ?>
    <input style="width: 26px;" type="text" name="kebo_twitter_options[kebo_twitter_cache_timer]" id="kebo_twitter_cache_timer" value="<?php echo esc_attr($options['kebo_twitter_cache_timer']); ?>" />
    <label class="description" for="kebo_twitter_cache_timer"><?php _e('Minutes. Should be between 5 and 60.', 'kebo_twitter'); ?></label>
    <p><?php _e('This controls how frequently we update the stored list of Tweets for display on your website.', 'kebo_twitter'); ?></p>
    <?php
}

/**
 * Sanitize and validate form input. Accepts an array, return a sanitized array.
 */
function kebo_twitter_options_validate($input) {

    $options = kebo_get_twitter_options();

    $output = array();

    if (isset($input['kebo_twitter_cache_timer']) && !empty($input['kebo_twitter_cache_timer'])) {
        
        if (is_numeric($input['kebo_twitter_cache_timer'])) {
            
            if (5 <= $input['kebo_twitter_cache_timer'] && 60 >= $input['kebo_twitter_cache_timer']) {
                
                $output['kebo_twitter_cache_timer'] = intval($input['kebo_twitter_cache_timer']);
                $type = 'updated';
                $message = __( 'Successfully updated.', 'kebo_twitter' );
                
                // On Successful Update, Refresh Tweet List
                kebo_twitter_get_tweets();
                
            } else {
                
                $type = 'error';
                $message = __( 'Value supplied is outside of acceptable range 5-60.', 'kebo_twitter' );
                
            }
            
        } else {
            
            $type = 'error';
            $message = __( 'Value supplied is not a valid number.', 'kebo_twitter' );
            
        }
    }

    add_settings_error(
        'kebo_twitter_cache_timer',
        esc_attr('settings_updated'),
        $message,
        $type
    );

    return apply_filters('kebo_twitter_options_validate', $output, $options);
}