<?php
/**
 * Plugin Name: ÖWA
 * Plugin URI:
 * Description: Adds the ÖWA statistics code to the website front-end
 * Version: 1.4.4
 * Author: <a href="http://service.ots.at/team">Jeremy Chinquist</a>
 *
 */

/**
 * OEWA class
 *
 * @class OEWA
 * @version 1.4.4
 * @author jjchinquist
 * @todo: improve oewa options form submission error output - too generic
 * @todo: on the oewa options form page label required input fields
 */
class OEWA {

    /**
     * @var array
     */
    protected $defaults = array(
        'options' => array(
            'oewa_account' => 'invalid',
            'oewa_default_category' => 'Service/Unternehmenskommunikation/Unternehmenskommunikation',
            'oewa_group' => 'invalid.at',
            'oewa_survey' => 1,
            'oewa_plus_account' => 0,
        ),
        'oewa_plugin_version' => '1.4.4',
        'oewa_tracking_code_version' => '2.0.1',
    );

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $testing_options;

    /**
     * @var int
     */
    protected $delta = 0;

    /**
     * @var boolean
     */
    protected $header_is_initialized = false;

    /**
     * @var boolean
     */
    protected $footer_is_initialized = false;

    /**
     *
     */
    public function __construct() {

        // installer
        register_activation_hook( __FILE__, array( $this, 'activate_oewa' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_oewa' ) );

        // settings
        $this->options = array_merge( $this->defaults['options'], (array) get_option( 'oewa_options' ) );
        $this->testing_options = get_option( 'oewa_testing_options' );

        // settings pages in administration
        add_action( 'admin_menu', array( $this, 'options_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2 );

        // settings for a specific post
        add_action( 'add_meta_boxes', array ($this, 'oewa_post_meta_data_box' ) );
        add_action('save_post', array($this, 'save_post'));

        // add to front end
        add_action( 'wp_head', array( $this, 'oewa_header_script' ) );
        add_action( 'wp_footer', array( $this, 'oewa_footer_script' ));

        // add to administration
        add_action( 'admin_head', array( $this, 'oewa_header_script' ) );
        add_action( 'admin_footer', array( $this, 'oewa_footer_script' ));

        // add to log in
        add_action( 'login_head', array( $this, 'oewa_header_script' ) );
        add_action( 'login_footer', array( $this, 'oewa_footer_script' ));

    }

    /**
     * Plugin activation.
     */
    public function activate_oewa() {
        add_option( 'oewa_options', $this->defaults['options']);
        add_option( 'oewa_plugin_version', $this->defaults['oewa_plugin_version']);
        add_option( 'oewa_tracking_code_version', $this->defaults['oewa_tracking_code_version']);
    }

    /**
     * Plugin update
     */
    public function update_oewa ()
    {
        // fetch the current databse value from the system
        $old_version = get_option( 'oewa_plugin_version' );

        // quick escape, the databse version contains the current plugin version contained in this php file
        // in which case, we are not processing an update
        if ($old_version === $this->defaults['oewa_plugin_version']) {
            return;
        }

        // go from oldest existing updates to newest. Eventually put into a separate file / class / function?
        switch ($old_version) {

            //from newest to oldest update change
            case '1.4.0' : // going to 1.4.1
            case '1.4.1' : // going to 1.4.2
            case '1.4.2' : // going to 1.4.3
            case '1.4.3' : // going to 1.4.4
                // no database structure changes

                // always go on, never break!

            default :
                // we should always land here
                update_option( 'oewa_tracking_code_version', $this->defaults['oewa_tracking_code_version']);
                update_option( 'oewa_plugin_version', $this->defaults['oewa_plugin_version']);
                break;
        }

    }

    /**
     * Deactivation
     *
     * @author jjchinquist
     */
    public function deactivate_oewa() {

        if ( $this->options['oewa']['deactivation_delete'] !== true ) {
            return;
        }

        // @todo: remove post meta data from database

        // deletes general options
        delete_option( 'oewa_options' );
        delete_option( 'oewa_testing_options' );
        delete_option( 'oewa_plugin_version' );
        delete_option( 'oewa_tracking_code_version' );
    }

    /**
     * Create options page in menu.
     */
    public function options_page() {

        // Add main page.
        $admin_page = add_menu_page( 'ÖWA: ' . __( 'General Settings' ), __( 'ÖWA', 'oewa' ), 'manage_options', 'oewa-options', array(
            $this,
            'options_page_output',
        ));

        add_submenu_page('oewa-options', 'ÖWA: ' . __( 'Test Category Mapping', 'oewa' ), __( 'Testing', 'oewa' ), 'manage_options', 'oewa-test-category-mapping', array(
            $this,
            'options_page_test_category_mapping_output',
        ));

    }

    /**
     * Add links to Settings page.
     *
     * @param     array $links
     * @param     string $file
     * @return     array
     */
    function plugin_settings_link( $links, $file ) {

        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return $links;
        }

        static $plugin;

        $plugin = plugin_basename( __FILE__ );

        if ( $file != $plugin ) {
            return $links;
        }

        $settings_link = sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php' ) . '?page=oewa-options', __( 'Settings' ) );
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Register settings.
     */
    public function register_settings() {

        // form 1
        register_setting( 'oewa_options', 'oewa_options', array( $this, 'validate_options' ) );

        add_settings_section( 'oewa_general', __( 'General settings' ), '', 'oewa_options' );
        add_settings_field( 'oewa_general_account', 'ÖWA ' . __( 'Account' ), array( $this, 'oewa_textfield_general_account' ), 'oewa_options', 'oewa_general' );
        add_settings_field( 'oewa_general_default_category', 'ÖWA ' . __( 'Default' ) . ' ' . __( 'Category' ), array( $this, 'oewa_textfield_general_default_category' ), 'oewa_options', 'oewa_general' );
        add_settings_field( 'oewa_general_group', 'ÖWA ' . __( 'Account' ) . ' ' . __( 'Group' ), array( $this, 'oewa_textfield_general_group' ), 'oewa_options', 'oewa_general' );
        add_settings_field( 'oewa_general_plus_account', 'ÖWA Plus', array( $this, 'oewa_checkbox_general_plus_account' ), 'oewa_options', 'oewa_general' );
        add_settings_field( 'oewa_general_survey', 'ÖWA Plus ' . __( 'Survey' ), array( $this, 'oewa_radio_button_general_survey' ), 'oewa_options', 'oewa_general' );

        add_settings_section( 'oewa_path', __( 'Path settings' ), '', 'oewa_options' );
        add_settings_field( 'oewa_path_mapping', 'ÖWA ' . __( 'Path' ), array( $this, 'oewa_double_textfield_mapping' ), 'oewa_options', 'oewa_path' );

        // form 2
        register_setting( 'oewa_testing_options', 'oewa_testing_options', array( $this, 'validate_options_testing_options' ) );
        add_settings_section( 'oewa_testing_options_path', __( 'Path' ), '', 'oewa_testing_options' );
        add_settings_field( 'oewa_testing_options_path_option_path', 'ÖWA ' . __( 'Path' ), array( $this, 'oewa_textfield_testing_option_path' ), 'oewa_testing_options', 'oewa_testing_options_path' );

    }

    /**
     * Add a text field for the option
     */
    public function oewa_textfield_general_account () {
        ?>
            <input type="text" size="50" maxlength="32" name="oewa_options[oewa_account]" value="<?php echo esc_html($this->options['oewa_account']); ?>">
        <?php
    }

    /**
     * Add a text field for the option
     */
    public function oewa_textfield_general_default_category () {
        ?>
            <input type="text" size="80" maxlength="150" name="oewa_options[oewa_default_category]" value="<?php echo esc_html($this->options['oewa_default_category']); ?>">
        <?php
    }

    /**
     * Add a text field for the option
     */
    public function oewa_textfield_general_group () {
        ?>
            <input type="text" size="50" maxlength="32" name="oewa_options[oewa_group]" value="<?php echo esc_html($this->options['oewa_group']); ?>">
        <?php
    }

    /**
     * Add a single checkbox input widget for the oewa_plus_account setting.
     * Per default it is inactive
     */
    public function oewa_checkbox_general_plus_account () {
        ?>
            <p class="description"><?php echo __('Special conditions apply for being an ÖWA Plus member (for example, the survey is non-optional). The ÖWA WordPress plugin will auto-manage some settings for you if you are an ÖWA Plus account.', 'oewa'); ?></p>
            <input type="checkbox" name="oewa_options[oewa_plus_account]" value="1" <?php if (1 === intval($this->options['oewa_plus_account'])) : ?>checked<?php endif; ?>> <?php echo __('I am an ÖWA Plus account', 'oewa'); ?><br>
        <?php
    }

    /**
     * Add a radio button input tag for the
     */
    public function oewa_radio_button_general_survey () {
        ?>
            <p class="description"><?php echo __('The ÖWA Survey is non-optional for ÖWA Plus members. This option will be forced to &quot;Opt in&quot; if you have checked the ÖWA Plus checkbox above.', 'oewa'); ?></p>
            <input type="radio" name="oewa_options[oewa_survey]" value="1" <?php if (1 === intval($this->options['oewa_survey'])) : ?>checked<?php endif; ?>> <?php echo __('Opt in', 'oewa'); ?><br>
            <input type="radio" name="oewa_options[oewa_survey]" value="0" <?php if (1 !== intval($this->options['oewa_survey'])) : ?>checked<?php endif; ?>> <?php echo __('Opt out', 'oewa'); ?><br>
        <?php
    }

    /**
     * Returns a table with multiple rows of textfield widgets for mapping purposes.
     *
     * @author jjchinquist
     */
    public function oewa_double_textfield_mapping ()
    {

        /**
         * @var array
         *     The keys are the paths, the values are the categories that will map to it
         */
        $mappings = (isset($this->options['oewa_path_mapping']) ? $this->options['oewa_path_mapping'] : array());

        $count = count($mappings);

        $counter = 0;

        $createThisManyBlanks = 5;

        ?>
            <p><?php echo __('Enter any number of path to category mappings. If additional rows are required, then submit the form and rows will be added. Paths <strong>may not</strong>
            start or end with &quot;/&quot;.', 'oewa'); ?></p>
            <p><strong><?php echo __('Important!'); ?></strong></p>
            <ol>
                <li><?php echo __('The form will automatically throw out any unmatched pairs. If either the path or the category column are empty in a single row, then the row will be skipped.', 'oewa'); ?></li>
                <li><?php echo __('Do not include the Protocol and Domain. The URL Path must be relative to the site_url() path', 'oewa'); ?> <i><?php echo site_url() . ('/' === substr(site_url(), -1) ? '' : '/'); ?></i>.</li>
                <li><?php echo __('If two patterns match a URL, then the one that is higher in this list will be used.', 'oewa'); ?></li>
                <li><?php echo __('If you are using post translations, then manage both URL schemes that are created.', 'oewa'); ?></li>
                <li><?php echo __('Lastly, please read the readme.txt concerning ÖWA audits and failure to comply to proper website categorization.', 'oewa'); ?></li>
            </ol>
            <p><?php echo __('Examples'); ?></p>
            <ol>
                <li><?php echo __('&quot;<strong>about/faq</strong>&quot;: matches the exact URL &quot;<i>/about/faq</i>&quot;', 'oewa'); ?></li>
                <li><?php echo __('&quot;<strong>about/*</strong>&quot;: matches pages such as &quot;<i>about/faq</i>&quot; or &quot;<i>about/testing/unit-testing/faqs</i>&quot;
                    but would not match &quot;<i>about</i>&quot;', 'oewa'); ?></li>
                <li><?php echo __('&quot;<strong>about/*/faq</strong>&quot;: matches pages such as &quot;<i>about/testing/faq</i>&quot; or &quot;<i>about/testing/unit-testing/faqs</i>&quot;
                    but would not match &quot;<i>about/faqs</i>&quot;', 'oewa'); ?></li>
            </ol>
            <table style="witdth: 100%;">
                <tr>
                   <th></th>
                   <th>URL Path (wildcard = *, max length 150)</th>
                   <th><?php echo __('Category'); ?> (max length 150)</th>
                </tr>
                <?php if ($count) : ?>
                    <?php /* Existing mappings */ ?>
                        <?php foreach ($mappings as $path => $category) : ?>
                            <tr>
                               <th><?php echo ($counter + 1); ?>.</th>
                                <td><input size="50" maxlength="150" name="oewa_options[oewa_path_mapping][path][<?php echo $counter; ?>]" value="<?php echo esc_html($path); ?>" type="text"></td>
                                <td><input size="50" maxlength="150" name="oewa_options[oewa_path_mapping][category][<?php echo $counter; ?>]" value="<?php echo esc_html($category); ?>" type="text"></td>
                            </tr>
                            <?php $counter++; ?>
                        <?php endforeach; ?>
                    <?php /* End existing mappings */ ?>
                <?php endif; ?>
                <?php /* New mappings */ ?>
                    <?php for ($i = 0; $i < $createThisManyBlanks; $i++) : ?>
                        <tr>
                            <th><?php echo ($counter + 1); ?>.</th>
                            <td><input size="50" maxlength="150" name="oewa_options[oewa_path_mapping][path][<?php echo $counter; ?>]" value="" type="text"></td>
                            <td><input size="50" maxlength="150" name="oewa_options[oewa_path_mapping][category][<?php echo $counter; ?>]" value="" type="text"></td>
                        </tr>
                        <?php $counter++; ?>
                    <?php endfor; ?>
                <?php /* End new mappings */ ?>
            </table>
        <?php
    }

    /**
     * Validate options.
     *
     * Resources:
     * http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
     * http://codex.wordpress.org/Data_Validation#Input_Validation
     *
     * @param array $input
     * @return array
     * @author jjchinquist
     */
    public function validate_options( $input )
    {

        // process reset
        if ( isset( $_POST['reset_oewa_options'] ) ) {
            $input = $this->defaults['options'];
            add_settings_error( 'oewa_settings_errors', 'oewa_settings_reset', __( 'Settings restored to defaults.' ), 'updated' );
        }

        // Note: sanitize, filter and validate in the order of the form options

        // sanitize and validate oewa_account
        if (empty($input['oewa_account'])) {
            add_settings_error( 'oewa_account', 'oewa_settings_oewa_account', __( 'String may not be empty' ));
        } else {
            $input['oewa_account'] = sanitize_text_field($input['oewa_account']);
            if (!strlen($input['oewa_account'])) {
                add_settings_error( 'oewa_account', 'oewa_settings_oewa_account', __( 'String may not be empty' ));
            }
        }

        // oewa_options[oewa_default_category] - all of oewa_account with added logic
        if (empty($input['oewa_default_category'])) {
            add_settings_error( 'oewa_default_category', 'oewa_settings_oewa_default_category', __( 'String may not be empty' ));
        } else {
            // remove starting and trailing slashes from the path
            // remove starting wildcard from the path
            $input['oewa_default_category'] = $this->stripCharactersFromStartOfString($input['oewa_default_category']);
            $input['oewa_default_category'] = $this->stripCharactersFromEndOfString($input['oewa_default_category']);

            $input['oewa_default_category'] = sanitize_text_field($input['oewa_default_category']);

            if (!strlen($input['oewa_default_category'])) {
                add_settings_error( 'oewa_default_category', 'oewa_settings_oewa_default_category', __( 'String may not be empty' ));
            }
        }

        // oewa_options[oewa_group] - same validation as oewa_account!
        if (empty($input['oewa_group'])) {
            add_settings_error( 'oewa_group', 'oewa_settings_oewa_group', __( 'String may not be empty' ));
        } else {
            $input['oewa_group'] = sanitize_text_field($input['oewa_group']);
            if (!strlen($input['oewa_group'])) {
                add_settings_error( 'oewa_group', 'oewa_settings_oewa_group', __( 'String may not be empty' ));
            }
        }

        // oewa_plus_account must be either 0 (Default) or 1
        $input['oewa_plus_account'] = (intval($input['oewa_plus_account']) ? 1 : 0);

        // oewa_survey must be either 1 (Opt in, Default) or 0 (Opt out). It CANNOT BE 0 if the setting $input['oewa_plus_account'] is 1
        $input['oewa_survey'] = (intval($input['oewa_survey']) ? 1 : 0);
        if (1 === $input['oewa_plus_account'] && 0 === $input['oewa_survey']) {
            $input['oewa_survey'] = 1;
            add_settings_error( 'oewa_survey', 'oewa_survey', __( 'You are an ÖWA Plus member and must opt in to the survey. To opt out of the ÖWA survey, first uncheck the ÖWA Plus box.', 'oewa' ), 'updated' ); // inform the person
        }

        // invalid items are simply thrown out here
        $input['oewa_path_mapping'] = $this->prepareMappingAsSingleArray($input['oewa_path_mapping']);

        // cache the oewa_path_mapping as an array that is prepared for
        // preg_match later on page load. Values will only change on the settings form update.
        $input['oewa_path_mapping_prepared'] = $this->prepareMappingAsPregmatches($input['oewa_path_mapping']);

        return $input;
    }

    /**
     * Validate the testing page
     *
     * Resources:
     * http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
     * http://codex.wordpress.org/Data_Validation#Input_Validation
     *
     * @param array $input
     * @return array
     * @author jjchinquist
     */
    public function validate_options_testing_options( $input )
    {

        // process reset
        if ( isset( $_POST['reset_oewa_testing_options'] ) ) {
            $input = array();
            add_settings_error( 'oewa_testing_options_errors', 'oewa_testing_options_reset', __( 'Settings restored to defaults.' ), 'updated' );
            return $input;
        }

        // remove starting and trailing slashes from the path
        $input['path'] = $this->stripCharactersFromStartOfString($input['path']);
        $input['path'] = $this->stripCharactersFromEndOfString($input['path']);

        // fetch the category from the path settings
        $input['category'] = $this->determineCategoryFromPathSettings($input['path']);
        if (false === $input['category']) {
            $input['category'] = $this->defaultCategoryPath (array (
                'admin' => (stristr($input['path'], 'wp-admin')), // assume administration paths have wp-admin in the url
                'home' => (0 === strlen($input['path']) ? true : false), // assume the start page is /
                'appendUrl' => false,
            ));
        }

        return $input;
    }

    /**
     * Add a text field for the testing
     */
    public function oewa_textfield_testing_option_path () {
        ?>
            <p class="description"><?php echo __('Enter an internal url path (do not include beginning and ending slashes). This form will print out the OEWA category that would be used for that page.<br /> <strong>Important:</strong> this form currently does not check if the URL matches an individual post. Do not check against the Post\'s URL.', 'oewa'); ?></p>
            <input type="text" size="80" maxlength="150" name="oewa_testing_options[path]" value="<?php echo (isset($this->testing_options) && isset($this->testing_options['path']) ? esc_html($this->testing_options['path']) : ''); ?>">
        <?php
    }

    /**
     * Collapses the double array (due to the settings form) into
     * a single array for our needs on the front-end.
     *
     * This function will automatically throw out any invalid input
     * because it is up to the administrator to monitor the proper mappings.
     *
     * @param array $mapping
     * @return array
     * @author jjchinquist
     */
    protected function prepareMappingAsSingleArray ($mapping) {

        // empty array, simply return
        if (empty($mapping)) {
            return array();
        }

        // move the form values from the two separate arrays into one settings array
        $valuesForTheDatabase = array();

        foreach ($mapping['path'] as $k => $path) {

            //if either the path or the category has been deleted, then we ignore the entire row
            // $mapping['path'][$k] always maps to $mapping['category'][$k]
            if (empty($path) || !strlen($path) || empty($mapping['category'][$k]) || !strlen($mapping['category'][$k])) {
                continue;
            }

            // validation logic:
            // remove starting and trailing slashes from the path
            $path = $this->stripCharactersFromStartOfString($path);
            $path = $this->stripCharactersFromEndOfString($path);

            // sanitize path
            $path = sanitize_text_field($path);

            if (!strlen($path)) {
                continue;
            }

            // validate category - may not have start or end slash
            $category = $mapping['category'][$k];
            $category = $this->stripCharactersFromStartOfString($category);
            $category = $this->stripCharactersFromEndOfString($category);

            // sanitize category
            $category = sanitize_text_field($category);

            $valuesForTheDatabase[$path] = $category;
        }

        return $valuesForTheDatabase;
    }

    /**
     * Strip a few custom unwanted characters from the beginning of a string. It is a custom validation function for this module.
     *
     * @param string $string
     * @param array $characters
     * @return string
     * @author jjchinquist
     */
    protected function stripCharactersFromStartOfString($string, $characters = array ('/')) {

        // nothing to do if characters is empty or string does not start with the character
        if (empty($characters) || !in_array(substr($string, 0, 1), $characters, true)) {
            return $string;
        }

        // @todo: change to preg_match or make recursive
        while (in_array(substr($string, 0, 1), $characters, true)) {
            $string = substr($string, 1);
        }

        return $string;
    }

    /**
     * Strip a few custom unwanted characters from the end of a string. It is a custom validation function for this module.
     *
     * @param string $string
     * @param array $characters
     * @return string
     * @author jjchinquist
     */
    protected function stripCharactersFromEndOfString($string, $characters = array ('/')) {

        // nothing to do if characters is empty or string does not end with the character
        if (empty($characters) || !in_array(substr($string, -1), $characters, true)) {
            return $string;
        }

        // @todo: change to preg_match or make recursive
        while (in_array(substr($string, -1), $characters, true)) {
            $string = substr( $string, 0, (strlen($string) - 1) );
        }

        return $string;
    }

    /**
     * The method to alter the form input (after it was cleaned) so that it is prepared
     * for page url preg_match calls.
     *
     * An adapted version of https://api.drupal.org/api/drupal/includes!path.inc/function/drupal_match_path/7
     * was used for this because we work together!
     * http://www.ots-blog.at/internet/jaenner-meetup-der-drupal-austria-wordpress-vienna-und-typo3-communities/
     *
     * @param array $mapping
     * @return array
     * @author jjchinquist
     */
    protected function prepareMappingAsPregmatches ($mapping) {

        if (empty($mapping) || !count($mapping)) {
            return array();
        }

        // Convert path settings to a regular expression.
        // /* with asterisks
        $to_replace = array(
            '/\\\\\*/', // asterisks
        );

        $replacements = array(
            '.*',
        );

        /**
         *
         */
        $mappings_altered = array();

        foreach ($mapping as $path_pattern => $category) {
            $pattern_quoted = preg_quote($path_pattern, '/');
            $pattern_altered = '/^(' . preg_replace($to_replace, $replacements, $pattern_quoted) . ')$/is';
            $mappings_altered[$pattern_altered] = $category;
        }

        return $mappings_altered;

    }

    /**
     * Options page output.
     *
     *  @author jjchinquist
     */
    public function options_page_output ()
    {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // some things should be done on plugin_update, but because there is currently
        // no hook that will do it properly, we do to the housekeeping during page load.
        $this->update_oewa();

        echo '
<div class="wrap">
    <h2>ÖWA</h2>
    <div class="oewa-settings">
        <div class="df-sidebar">
            <div class="df-credits">
                <h3 class="hndle">ÖWA Plugin Version ' . $this->defaults['oewa_plugin_version'] . '</h3>
                <div class="inside">
                    <h4 class="inner">' . __( 'Information' ) . '</h4>
                    <ol>
                        <li>' . __( 'The current tracking pixel version that this plugin implements is:', 'oewa' ) . '  ' . $this->defaults['oewa_tracking_code_version'] . '</li>
                        <li>' . __( 'The ÖWA website', 'oewa' ) . ': <a href="http://www.oewa.at" target="_blank" title="ÖWA" target="_blank" >ÖWA.at</a>.</li>
                        <li>' . __( 'The embed code', 'oewa' ) . ': <a href="http://www.oewa.at/basic/implementierung" target="_blank" title="ÖWA">' . __( 'Tracking pixel documentation', 'oewa' ) . '</a>.</li>
                        <li>' . __( 'Categorization', 'oewa' ) . ': <a href="http://www.oewa.at/basic/implementierung" target="_blank" title="ÖWA">' . __( 'Categorization documentation', 'oewa' ) . '</a>.</li>
                    </ol>
                    <p class="df-link inner">' . __( 'Plugin created by' ) . ' <strong>Jeremy Chinquist</strong>, Web Developer, <a href="http://service.ots.at/" target="_blank" title="APA-OTS Originaltext-Service GmbH">APA-OTS Originaltext-Service GmbH</a></p>
                </div>
            </div>
            <form action="options.php" method="post">';
                settings_fields( 'oewa_options' );
                do_settings_sections( 'oewa_options' );
                echo '
                <p class="submit">';
                    submit_button( '', 'primary', 'save_oewa_options', false );
                    echo ' ';
                    submit_button( __( 'Reset to defaults' ), 'secondary', 'reset_oewa_options', false );
                    echo '
                </p>
            </form>
        </div>
    </div>
    <div class="clear"></div>
</div>';

    }

    /**
     * Options page output.
     *
     *  @author jjchinquist
     */
    public function options_page_test_category_mapping_output ()
    {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '
<div class="wrap">
    <h2>ÖWA</h2>
    <div class="oewa-settings">
        <p>Enter an internal URL and subit the form to check that the URL to Category pattern matching is correct. If you feel that it is incorrect, report it as an issue (include screenshots).</p>
        <form action="options.php" method="post">';
            settings_fields( 'oewa_testing_options' );
            do_settings_sections( 'oewa_testing_options' );
            echo '
            <p>The category string for this path is: <i>' . esc_html($this->testing_options['category']) . '</i></p>
            <p class="submit">';
                submit_button( __( 'Test' ), 'primary', 'save_oewa_testing_options', false );
                echo ' ';
                submit_button( __( 'Reset to defaults' ), 'secondary', 'reset_oewa_testing_options', false );
                echo '
            </p>
        </form>
    </div>
    <div class="clear"></div>
</div>';

    }

    /**
     * Output the ÖWA head script as inline. Becuase some elements are settings, we must do it using oewa_head and
     * cannot do it by way of wp_enqueue_scripts.
     *
     * Note that this hook is not called in the administration theme
     *
     * @author jjchinquist
     */
    public function oewa_header_script ()
    {

        // make certain the header is only added once
        if (isset($this->header_is_initialized) && $this->header_is_initialized) {
            return;
        }

        // the account has already been forced to non-empty, but it cannot be the default either
        if ($this->defaults['options']['oewa_account'] === $this->options['oewa_account']) {
            return;
        }

        $this->header_is_initialized = true;

        $category_path = esc_js($this->determineCategory());

        $this->options['oewa_account'] = esc_js($this->options['oewa_account']);

        echo <<<JS
<script type="text/javascript">
    //<![CDATA[
        var OEWA = {
            "s":"{$this->options['oewa_account']}",
            "cp":"{$category_path}"
        };

        var oewaq = oewaq || [];

        oewaq.push(OEWA);
    //]]>
</script>
JS;
    }

    /**
     * https://api.drupal.org/api/drupal/includes!path.inc/function/drupal_match_path/7
     *
     * @author jjchinquist
     */
    protected function determineCategory ()
    {
        // if the post has overridden the category, fetch that
        if ($category_path = $this->determineCategoryFromPostField()) {
            return $category_path;
        }

        // fetch the category from the path settings
        if ($category_path = $this->determineCategoryFromPathSettings()) {
            return $category_path;
        }

        // nothing fits, display the default
        return $this->defaultCategoryPath ();
    }

    /**
     * Determines category based on the current post field. If it is not a post full page, or the post
     * does not have a value set, then it returns false.
     *
     * @return string|boolean
     *     Returns the category string if found in the post field. Else returns false.
     * @author jjchinquist
     */
    protected function determineCategoryFromPostField ()
    {
        global $post;

        if (empty($post) || empty($post->ID)) {
            return false;
        }

        $category = get_post_meta ( $post->ID, '_oewa_category');

        if (empty($category) || empty($category[0]) || !strlen($category[0])) {
            return false;
        }

        return $category[0];
    }

    /**
     * Determines category based on the path to category mapping. This is the second level
     * which is used if the post field is not set.
     *
     * An adapted version of https://api.drupal.org/api/drupal/includes!path.inc/function/drupal_match_path/7
     * was used for this because we work together!
     * http://www.ots-blog.at/internet/jaenner-meetup-der-drupal-austria-wordpress-vienna-und-typo3-communities/
     *
     * @param string $current_path
     *     [optional] Default is the current interal url that is being viewed. If provided, then
     *     the category will be returned for this path and not the current path being viewed.
     * @return string|boolean
     *     Returns the category string if found in the mapping. Else returns false.
     * @author jjchinquist
     */
    protected function determineCategoryFromPathSettings ($current_path = null)
    {

        // use oewa_path_mapping_prepared
        if (empty($this->options['oewa_path_mapping_prepared']) || !count($this->options['oewa_path_mapping_prepared'])) {
            return false;
        }

        // load the current url if no path was provided
        if (empty($current_path)) {
            global $wp;
            $current_path = add_query_arg(array(), $wp->request);
        }

        // for our matching, strip starting and trailing slash
        $current_path = $this->stripCharactersFromStartOfString($current_path);
        $current_path = $this->stripCharactersFromEndOfString($current_path);

        // return on the first match
        foreach ($this->options['oewa_path_mapping_prepared'] as $pattern => $category) {
            if (preg_match($pattern, $current_path)) {
                return $category;
            }
        }

        return false;
    }

    /**
     * Determines category based on the path to category mapping. This is the third and final level
     * which is used if the post field is not set and the current URL does not match one of the defined patterns.
     *
     * @param array $options
     *     <p>Override some of the default behaviour, usually reserved for debugging and special sites. All
     *     of the options here are optional and should not be overridden unless known.</p>
     *     <ul>
     *         <li>home: boolean [optional]. Default is is_home(). Override to fetch the category for the home page.</li>
     *         <li>admin: boolean [optional]. Default is is_admin(). Override to indicate explicitly that the category is an administration page.</li>
     *         <li>appendUrl: boolean [optional]. Default is true. The current url path is appended to the ÖWA category. This is old behaviour which may change.</li>
     *     </ul>
     * @return string
     * @author jjchinquist
     */
    protected function defaultCategoryPath (array $options = array())
    {
        $defaultOptions = array(
            'home' => is_home(),
            'admin' => is_admin(),
            'appendUrl' => true,
        );

        $options = array_merge($defaultOptions, $options);

        // force to true/false (currently all options are true/false)
        foreach ($options as $k => $v) {
            $options[$k] = ($v ? true : false);
        }

        $category_path = array (
            $this->options['oewa_default_category'],
        );

        if ($options['home']) {
            // @todo: home should be a separate variable in the settings form
            $category_path[] = 'index';
        } else if ($options['admin']) {
            // @todo there should be a default administration oewa variable
            $category_path[] = 'admin';
        } else if ($options['appendUrl']) {

            // @todo: the remaining logic here will append current url parameters to the category string. It was required in the past but probobly is not required any more.

            // strip any fragment/query parameters from the request URI - ÖWA does not handle it
            $url = $_SERVER['REQUEST_URI'];

            if (stristr($url, '?')) {
                $url = substr($url, 0, strpos($url, '?'));
            }

            $category_path = array_merge($category_path, explode('/', $url));
        }

        // throw out any empty items - just in case
        foreach ($category_path as $k => $v) {
            if (strlen($v)) {
                continue;
            }

            unset($category_path[$k]);
        }

        // make it a string now
        $category_path = implode('/', $category_path);

        // ÖWA does not allow > 150 characters, truncate
        if (150 < strlen($category_path)) {
            $category_path = substr ($category_path, 0, 150);
        }

        // strip ending slash (possible due to truncation)
        $category_path = $this->stripCharactersFromEndOfString($category_path);

        return $category_path;
    }

    /**
     * Output the ÖWA footer script as inline JS due to parameter
     * options in the settings page
     *
     * @author jjchinquist
     */
    public function oewa_footer_script ()
    {

        // make certain the footer is only added once
        if (isset($this->footer_is_initialized) && $this->footer_is_initialized) {
            return;
        }

        $this->footer_is_initialized = true;

        // the account has already been forced to non-empty, but it cannot be the default either
        if ($this->defaults['options']['oewa_account'] === $this->options['oewa_account']) {
            return;
        }

        $survey = ($this->options['oewa_survey'] ? 'true' : 'false');

        echo <<<JS
<script type="text/javascript">
    //<![CDATA[
        (function() {
            oewaconfig = {"survey":{$survey}};
            var scr = document.createElement('script');
            scr.type = 'text/javascript'; scr.async = true;
            scr.src = '//dispatcher.oewabox.at/oewa.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(scr, s);
        })();
    //]]>
</script>
JS;
    }

    /**
     * Add a meta data box to the post add/edit page
     */
    public function oewa_post_meta_data_box ()
    {
        add_meta_box (
            'oewa_box',
            'ÖWA', // not translatable
            array( $this, 'oewa_box_content' )
        );
    }

    /**
     * Displays the meta box content on the post add/edit form page
     *
     * @param unknown $post
     */
    public function oewa_box_content ( $post ) {
        ?>
            <h3>ÖWA <?php echo __('Post Category', 'oewa') ?>: </h3>
            <p class="description"><?php echo __('Use this option to override the default ÖWA Category that would be printed to the page. In most cases this should be left blank. See the ÖWA Settings page for more information.', 'oewa'); ?></p>
            <input type="text" id="oewa[category]" name="oewa[category]" style="width: 100%" value="<?php echo esc_html(get_post_meta($post->ID, '_oewa_category', true)); ?>" />
        <?php
    }

    /**
     * When a post is saved to the database (either inserted or updated) then
     * add the post specific ÖWA category field to the options.
     *
     * @param int $post_id
     * @author jjchinquist
     */
    public function save_post ($post_id)
    {

        // if the data was not a part of the POST array, then do nothing to the existing category in the database (if there is any)
        if (empty($_POST['oewa'])) {
            return $post_id;
        }

        $metadata = $_POST['oewa']['category'];

        // cases where the category should be considered NULL
        if (!is_string($metadata) || !strlen($metadata)) {
            $metadata = '';
        }

        // validate $metadata - may not have start or end slash
        $metadata = $this->stripCharactersFromStartOfString($metadata);
        $metadata = $this->stripCharactersFromEndOfString($metadata);

        // sanitize $metadata
        $metadata = sanitize_text_field($metadata);

        if (add_post_meta($post_id, '_oewa_category', $metadata, true)) {
            return $post_id;
        }

        update_post_meta($post_id, '_oewa_category', $metadata);
        return $post_id;
    }
}

/**
 * @var OEWA
 */
$oewa = new OEWA ();