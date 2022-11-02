<?php
/*
 * References:
 * - https://docs.gravityforms.com/gffeedaddon/
 * - https://docs.gravityforms.com/category/developers/php-api/add-on-framework/settings-api/
 */

GFForms::include_feed_addon_framework();

class GFCloudStorage extends GFFeedAddOn {

	private $provider = 'Nextcloud';

	protected $_version = GF_CLOUD_STORAGE_VERSION;
	protected $_min_gravityforms_version = '1.9.16';
	protected $_slug = 'gf-cloud-storage';
	protected $_path = 'gf-cloud-storage/gf-cloud-storage.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Gravity Forms Cloud Storage';
	protected $_short_title = 'Cloud Storage';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFCloudStorage
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFCloudStorage();
		}

		return self::$_instance;
	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support( [
            'option_label' => esc_html__( 'Subscribe contact to service x only when payment is received.', 'gf_cloudstorage' )
        ] );

	}


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Process the feed e.g. subscribe the user to a list.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return bool|void
	 */
	public function process_feed( $feed, $entry, $form ) {

		$settings = $this->get_plugin_settings();

		$protocol= rgar( $settings, 'storage_protocol' );
		$endpoint = rgar( $settings, 'storage_endpoint' );

		$username = GFCommon::replace_variables($feed['meta']['storage_username'], $form, $entry);
		$password = GFCommon::replace_variables($feed['meta']['storage_password'], $form, $entry);
		$folder   = GFCommon::replace_variables($feed['meta']['storage_folder'], $form, $entry);
		$filename = sanitize_file_name(GFCommon::replace_variables($feed['meta']['storage_filename'], $form, $entry) . '-' . $entry['id'] . '.html');
		$filehead = GFCommon::replace_variables($feed['meta']['storage_fileheader'], $form, $entry, false, true, false);
		$filedata = GFCommon::replace_variables('{all_fields}', $form, $entry, false, true, false);
		$filefoot = GFCommon::replace_variables($feed['meta']['storage_filefooter'], $form, $entry, false, true, false);

		// Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = [];
		foreach ( $field_map as $name => $field_id ) {

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $this->get_field_value( $form, $entry, $field_id );

		}

		$data = $filehead . $filedata . $filefoot;

		$this->upload($protocol, $endpoint, $username, $password, $folder, $filename, $data);
	}

	/**
	 * Creates a custom page for this add-on.
	 */
	public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return [ [
            'title'  => esc_html__( 'Cloud Storage Settings', 'gf_cloudstorage' ),
            'fields' => [ [
                'label'   => 'Protocol',
                'label'   => esc_html__( 'Protocol', 'gf_cloudstorage' ),
                'type'    => 'select',
                'name'    => 'storage_protocol',
                'tooltip' => esc_html__( 'Is the endpoint secure?', 'gf_cloudstorage' ),
                'choices' => [ [
                    'label' => 'HTTP',
                    'value' => 'http://'
                ], [
                    'label' => 'HTTPS',
                    'value' => 'https://'
                ] ] 
            ], [
                'name'    => 'storage_endpoint',
                'tooltip' => esc_html__( 'Endpoint to post to, e.g. https://cloud.orwa.org/remote.php/dav/files/', 'gf_cloudstorage' ),
                'label'   => esc_html__( 'API Endpoint', 'gf_cloudstorage' ),
                'type'    => 'text',
                'class'   => 'small',
            ], ],
        ], ];
	}

	/**
	 * Configures the settings which should be rendered on the feed edit page in the Form Settings > Cloud Storage area.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
        return [ [
            'title'  => $this->provider . ' Integration Settings',
            'fields' => [ [
                'label'             => 'Name This Integration',
                'type'              => 'text',
                'name'              => 'storage_name',
                'tooltip'           => 'Expecially useful for multiple cloud storage integrations.',
                'class'             => 'medium',
                'feedback_callback' => [ $this, 'is_valid_setting' ]
            ], [
                'label'             => $this->provider . ' Username',
                'type'              => 'text',
                'name'              => 'storage_username',
                'tooltip'           => 'Your username for the cloud storage provider.',
                'class'             => 'medium merge-tag-support mt-position-right',
                'feedback_callback' => [ $this, 'is_valid_setting' ]
            ], [
                'label'             => $this->provider . ' Password',
                'type'              => 'text',
                'name'              => 'storage_password',
                'tooltip'           => 'Your password for the cloud storage provider.',
                'class'             => 'medium merge-tag-support mt-position-right',
                'feedback_callback' => [ $this, 'is_valid_setting' ]
            ], [
                'label'             => $this->provider . ' Folder',
                'type'              => 'text',
                'name'              => 'storage_folder',
                'tooltip'           => 'This is the path we want to save our file in.  Default: &quot;/&quot;',
                'class'             => 'medium merge-tag-support mt-position-right',
                'feedback_callback' => [ $this, 'is_valid_setting' ]
            ], [
                'label'             => $this->provider . ' Filename',
                'type'              => 'text',
                'name'              => 'storage_filename',
                'tooltip'           => 'Name of the file, feel free to use merge tags!',
                'class'             => 'medium merge-tag-support mt-position-right',
                'feedback_callback' => [ $this, 'is_valid_setting' ]
            ], [
				'label'             => 'Document Header',
				'type'              => 'textarea',
				'name'              => 'storage_fileheader',
				'tooltip'           => 'HTML Header',
				'class'             => 'medium merge-tag-support mt-position-right',
                'allow_html'        => true
			], [
				'label'             => 'Document Footer',
				'type'              => 'textarea',
				'name'              => 'storage_filefooter',
				'tooltip'           => 'HTML Footer',
				'class'             => 'medium merge-tag-support mt-position-right',
                'allow_html'        => true
			], ],
        ] ];
    }

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return [
			'storage_name'  => esc_html__( 'Integration Name', 'gf_cloudstorage' ),
		];
	}

	/**
	 * Format the value to be displayed in the storage_name column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_storage_name( $feed ) {
		return '<b>' . rgars( $feed, 'meta/storage_name' ) . '</b>';
	}

	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {
		return true;
	}

	/**
     * @see   parent
	 * @see   credit: [Heroicons](https://heroicons.com/)
     *
     * @since 1.6.0
     */
    public function get_menu_icon() {
		$icon = file_get_contents(plugin_dir_path( __FILE__) . 'img/icon.svg' );
        return apply_filters('cloud_storage_icon', $icon, 1 );
    }

	// This private function is what makes it "nextcloud" - therefore we do not need options like
	// endpoint ... we should prolly have "domain" tho
	// @TODO: Conditionally handle empty data based on form option to send HTML or PDF file
	private function upload($protocol, $domain, $username, $password, $folder, $filename, $data = '') {

		try {
			// $filename = 'entry-1234.pdf';
			$endpoint = $protocol . $domain . '/' . $username . '/' . $folder . '/' . $filename;

			// $mimeType = 'application/pdf';
			$mimeType = 'text/html';

			// $data = new \CURLFile('./' . $filename, $mimeType, $filename);

			$ch = curl_init($endpoint);

			curl_setopt($ch, CURLOPT_URL, $endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: ' . $mimeType
			]); 

			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

			$results = curl_exec($ch);

			// Check the return value of curl_exec(), too
			if ($results === false) {
				throw new Exception(curl_error($ch), curl_errno($ch));
			}

			// Check HTTP return code, too; might be something else than 200
			$httpReturnCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		} catch(Exception $e) {

			trigger_error(sprintf(
				'Curl failed with error #%d: %s',
				$e->getCode(), $e->getMessage()),
				E_USER_ERROR);

		} finally {
			// Close curl handle unless it failed to initialize
			if (is_resource($ch)) {
				curl_close($ch);
			}
		}
	}

}