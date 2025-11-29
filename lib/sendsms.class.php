<?php
require_once 'functions.php';
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SendSMS {
	var $functions;

	function __construct() {
		$this->functions = new SendsmsFunctions();
	}

	/**
	 * Send a message with sendsms
	 *
	 * @since 1.0.0
	 */
	function message_send( $short, $gdpr, $to, $content, $type, $suffix = '' ) {
		global $wpdb;
		$content = $content;
		$this->functions->get_auth( $username, $password, $label );
		$to              = $this->functions->validate_phone( $to );
		$args['headers'] = array(
			'url' => get_site_url(),
		);
		$results         = array();
		if ( strtolower( $type ) === 'code' ) {
			if ( ! strpos( $content, '{code}' ) ) {
				$content .= '{code}';
			}
			$code       = $this->functions->generateVerificationCode( $to, $suffix );
			$newContent = str_replace( '{code}', $code, $content );
			$results    = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=message_send&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&from=' . urlencode( $label ) . '&to=' . urlencode( $to ) . '&text=' . urlencode( $newContent ), $args ) ), true );
		} else {
			$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=message_send' . ( $gdpr ? '_gdpr' : '' ) . '&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&from=' . urlencode( $label ) . '&to=' . urlencode( $to ) . '&text=' . urlencode( $content ) . '&short=' . ( $short ? 'true' : 'false' ), $args ) ), true );
		}
		$table_name = $wpdb->prefix . 'sendsms_dashboard_history';
		$wpdb->query(
			$wpdb->prepare(
				"
                INSERT INTO $table_name
                (`phone`, `status`, `message`, `details`, `content`, `type`, `sent_on`)
                VALUES ( %s, %s, %s, %s, %s, %s, %s)",
				$to,
				isset( $results['status'] ) ? $results['status'] : '',
				isset( $results['message'] ) ? $results['message'] : '',
				isset( $results['details'] ) ? $results['details'] : '',
				$content,
				$type,
				date( 'Y-m-d H:i:s' )
			)
		);
		return $results;
	}

	/**
	 * Create and send batch messages
	 *
	 * @since 1.0.1
	 */
	function send_batch( $phones, $message ) {
        if (!file_exists(SENDSMS_DASHBOARD_PLUGIN_DIRECTORY.'batches')) {
            if (!mkdir($concurrentDirectory = SENDSMS_DASHBOARD_PLUGIN_DIRECTORY.'batches') && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
		if ( $file = fopen( SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . 'batches/batch.csv', 'w' ) ) {
			$this->functions->get_auth( $username, $password, $label );
			$headers = array(
				'message',
				'to',
				'from',
			);
			fputcsv( $file, $headers );
			foreach ( $phones as $phone ) {
				fputcsv(
					$file,
					array(
						$message,
						$phone,
						$label,
					)
				);
			}
			// $start_time = '2970-01-01 02:00:00';
			$start_time = date('Y-m-d H:i:s');
			$name       = 'WordPress - ' . get_site_url() . ' - ' . uniqid();
			$data       = file_get_contents( SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . '/batches/batch.csv' );
			$results    = json_decode(
				wp_remote_retrieve_body(
					wp_remote_post(
						'https://api.sendsms.ro/json?action=batch_create&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&start_time=' . urlencode( $start_time ) . '&name=' . urlencode( $name ),
						array(
							'body' => array( 'data' => $data ),
						)
					)
				),
				true
			);
			if ( ! isset( $results['status'] ) || $results['status'] < 0 ) {
				echo json_encode( $results );
				wp_die();
			}
			// log into history table
			global $wpdb;
			$table_name = $wpdb->prefix . 'sendsms_dashboard_history';
			$wpdb->query(
				$wpdb->prepare(
					"
                INSERT INTO $table_name
                (`phone`, `status`, `message`, `details`, `content`, `type`, `sent_on`)
                VALUES ( %s, %s, %s, %s, %s, %s, %s)
            ",
					__( 'Go to hub.sendsms.ro', 'sendsms-dashboard' ),
					isset( $results['status'] ) ? $results['status'] : '',
					isset( $results['message'] ) ? $results['message'] : '',
					isset( $results['details'] ) ? $results['details'] : '',
					__( 'We created your campaign. Go and check the batch called: ', 'sendsms-dashboard' ) . $name,
					__( 'Batch Campaign', 'sendsms-dashboard' ),
					date( 'Y-m-d H:i:s' )
				)
			);
			fclose( $file );
			if ( ! unlink( SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . '/batches/batch.csv' ) ) {
				return 'Unable to delete previous batch file! Please check file/folder permissions (' . SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . '/batches/batch.csv)';
			}
			return $results;
		} else {
			return 'Unable to open/create batch file! Please check file/folder permissions (' . SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . 'batches/batch.csv)';
		}
	}

	/**
	 * Get user balance
	 *
	 * @since 1.0.0
	 */
	function get_user_balance() {
		$this->functions->get_auth( $username, $password, $label );
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://api.sendsms.ro/json?action=user_get_balance&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) ) ), true );
		return $results;
	}

	/**
	 * Create a group on sendsms.ro
	 *
	 * @since 1.0.0
	 */
	function create_group() {
		$this->functions->get_auth( $username, $password, $label );
		$name    = 'WordPress - ' . get_site_url();
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=address_book_group_add&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&name=' . urldecode( $name ) ) ), true );
		return $results;
	}

	/**
	 * Delete a group
	 *
	 * @since 1.0.0
	 */
	function delete_group( $id ) {
		$this->functions->get_auth( $username, $password, $label );
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=address_book_group_delete&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&group_id=' . $id ) ), true );
		return $results;
	}

	/**
	 * Get all groups from sendsms.ro
	 *
	 * @since 1.0.0
	 */
	function get_groups() {
		 $this->functions->get_auth( $username, $password, $label );
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=address_book_groups_get_list&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) ) ), true );
		return $results;
	}

	/**
	 * Add a contact to a group
	 *
	 * @since 1.0.0
	 */
	function add_contact( $group_id, $first_name, $last_name, $phone_number ) {
		$this->functions->get_auth( $username, $password, $label );
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=address_book_contact_add&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&group_id=' . urlencode( $group_id ) . '&phone_number=' . urlencode( $phone_number ) . '&first_name=' . urlencode( $first_name ) . '&last_name=' . urlencode( $last_name ) ) ), true );
		return $results;
	}

	/**
	 * Delete a contact from sendsms
	 *
	 * @since 1.0.0
	 */
	function delete_contact( $id ) {
		$this->functions->get_auth( $username, $password, $label );
		$results = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://api.sendsms.ro/json?action=address_book_contact_delete&username=' . urlencode( $username ) . '&password=' . urlencode( $password ) . '&contact_id=' . urlencode( $id ) ) ), true );
		return $results;
	}
}
