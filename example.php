<?php
/**
 * Example Usage.
 *
 * Docs:
 * API: https://developer.intuit.com/docs/api/
 * API Sample Code: https://github.com/IntuitDeveloperRelations/SampleCodeSnippets/tree/master/APISampleCode/V3QBO
 * SDK Docs: https://github.com/intuit/QuickBooks-V3-PHP-SDK/blob/master/README.md
 */

/**
 * The `qbo_connect_initiated` hook is called when a proper authenticated connection is established.
 */
function example_qp_api_connect_iniated( $api ) {
	$data_service = $api->get_qb_data_service();

	$message = qp_api_get_company_info( $data_service );
	$message .= qp_api_query_customer( $data_service, 'ACME Company' );
	$message .= qp_api_create_customer( $data_service, array(
		'BillAddr' => array(
			'Line1'                  => '1 Infinite Loop',
			'City'                   => 'Cupertino',
			'Country'                => 'USA',
			'CountrySubDivisionCode' => 'CA',
			'PostalCode'             => '95014'
		),
		'Notes'              => 'Test... cras justo odio, dapibus ac facilisis in, egestas eget quam.',
		'GivenName'          => 'Justin',
		'FamilyName'         => 'Sternberg',
		'FullyQualifiedName' => 'Zao',
		'CompanyName'        => 'Zao',
		'DisplayName'        => 'Zao',
		'PrimaryPhone'       =>  array(
			'FreeFormNumber' => '(408) 606-5775'
		),
		'PrimaryEmailAddr' =>  array(
			'Address' => 'jt@example.com',
		)
	) );

	wp_die( $message );
}
add_action( 'qbo_connect_initiated', 'example_qp_api_connect_iniated' );

function qp_api_get_company_info( $data_service ) {
	$company = $data_service->get_company_info();

	if ( is_wp_error( $company ) ) {
		return wpautop( $company->get_error_message() );
	}

	$props = array();
	foreach ( get_object_vars( $company ) as $prop_name => $prop_value ) {
		$props[] = '<tr><td>'. print_r( $prop_name, true ) .'</td><td>'. print_r( $prop_value, true ) .'</td></tr>';
	}

	$message = '
	<h2>Get Company Info</h2>
	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th>' . __( 'Connected Company Name', 'qbo-connect-ui' ) . '</th>
				<th>' . __( 'Connected Company ID', 'qbo-connect-ui' ) . '</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>'. esc_html( $company->CompanyName ) .'</td>
				<td>'. esc_html( $company->Id ) .'</td>
			</tr>
		</tbody>
	</table>
	<br>
	<table class="wp-list-table widefat">
		<thead>
			<tr>
				<th>'. __( 'Company Property:', 'qbo-connect-ui' ) .'</th>
				<th>'. __( 'Company Property Value:', 'qbo-connect-ui' ) .'</th>
			</tr>
		</thead>
		<tbody>
			'. implode( "\n", $props ) .'
		</tbody>
	</table>
	';

	return $message;
}

function qp_api_query_customer( $data_service, $company_name_to_search ) {
	global $wpdb;

	$query = $wpdb->prepare(
		"SELECT * FROM Customer WHERE CompanyName = %s",
		$company_name_to_search
	);

	$customers = $data_service->query( $query );

	$error = $data_service->getLastError();

	$message = '<h2>Query Customer: ' . $company_name_to_search . '</h2>';

	if ( $error ) {
		$message .= "<p>The Status code is: " . $error->getHttpStatusCode() . "\n</p>";
		$message .= "<p>The Helper message is: " . $error->getOAuthHelperError() . "\n</p>";
		$message .= "<p>The Response message is: " . $error->getResponseBody() . "\n</p>";
	} else {
		$message = print_r( $customers, true );
	}

	return $message;
}

function qp_api_create_customer( $data_service, $customer_args ) {
	$message = '<h2>Create Customer</h2>';

	list( $customer, $result ) = $data_service->create_customer( $customer_args );

	$message .= print_r( compact( 'customer', 'result' ), true );

	return $message;
}
