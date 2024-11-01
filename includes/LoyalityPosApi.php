<?php 
if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }

/**
 *  Loyality pos api. Available to implement pos systems and 
 *  websites that want to use Loyality's gift cards, vouchers, 
 *  and loyalty program. 
 * 
 * Author: Philip Neves (Neves Software Inc.)
 * Copyright: © 2019 Neves Software Inc.
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// Testing
//define('LOYALITY_BASE_URL', "https://dev.loyality.app");

// LIVE 
define('LOYALITY_BASE_URL', "https://loyality.app");

class LoyalityPosApi 
	{
	var $apiKey = null; 
	var $organizationId = null;
	var $locationId = null; 
	var $cashierNo;
	var $siteBase = null;  
	var $baseUrl = null;
	
	var $errors = null;


	/**
	 * Constructor.
	 */
	function __construct()
		{
		
		$siteBase = LOYALITY_BASE_URL;
		$this->baseUrl = $siteBase . '/pos-api';
		}
	
	/** setApiKey
	 * 
	 * Sets api access key. Or POS Software Key. 
	 * 
	 * @param type $apiKey
	 */
	public function setApiKey($apiKey)
		{
		$this->apiKey = $apiKey; 
		}


	/** Set organization Id. 
	 * 
	 * @param type $organizationId
	 */

	public function setOrganizationId($organizationId)
		{
		$this->organizationId = $organizationId;
		}

	/** set location id. 
	 * 
	 * @param type $location_id
	 */	
	public function setLocationid($location_id)
		{
		$this->locationId = $location_id;
		}


	/**
	 * set the cashier who created the transaction.
	 * Can be any unique identifier.
	 *
	 * @param [type] $cashier_no
	 * @return void
	 */
	public function setCashierNo($cashier_no)
		{
		$this->cashierNo = $cashier_no;
		}
		

	/** get_gift_card_load_amounts
	 * 
	 * gets the predefined gift card load amounts. 
	 * 
	 */
	public function get_gift_card_load_amounts()
		{
		// $handle = curl_init();
		
		$url = $this->baseUrl . '/get-gift-card-load-amounts';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array; 
		}

	/** get_gift_card_templates
	 * 
	 * Get gift card templates to issue virtual cards. 
	 */
	public function get_gift_card_templates()
		{
		//$handle = curl_init();
		
		$url = $this->baseUrl . '/get-gift-card-templates';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, TRUE); 
		
		return $array; 
		}

	/** issue gift card. 
	 * 
	 * 
	 */
	public function issue_gift_card($data)
		{
		// $handle = curl_init();
		
		$url = $this->baseUrl . '/issue-gift-card';

		if ($this->organizationId == null) 
			{
			$this->errors = "No organization Id Provided."; 
			return false;
			}

		if ($this->apiKey == null)
			{
			$this->errors = "No api key provided";
			return false;
			}

		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $data['gift_card_id']
			    ];

		if (array_key_exists('template_id', $data))
			{
			$postData['template_id'] = $data['template_id'];
			}
		else 
			{
			$this->errors = "Gift card template required."; 

			return false; 
			}
		
		if (array_key_exists('gift_card_amount', $data))
			{
			$postData['gift_card_amount'] = $data['gift_card_amount'];
			}
		
		else 
			{
			$this->errors = "No gift card amount provided.";
			
			return false;
			}
			
		if (array_key_exists('email', $data))
			{
			$postData['email'] = $data['email'];
			}
			
		elseif(array_key_exists('phone', $data))
			{
			$postData['phone'] = $data;
			}
			
		else 
			{
			$this->errors = "no recipient method provided. Please add either an email or phone number"; 
			
			return false;
			}
		
		if (array_key_exists('message', $data))
			{
			$postData['message'] = $data['message'];
			}
		
		if (array_key_exists('recipient_name', $data))
			{
			$postData['recipient_name'] = $data['recipient_name'];
			}
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
			
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 

		if ($array['success'] == false)
			{
			$this->errors = $array['errors']; 

			return false;
			}
		
		
		return $array;
		}
		
	/**
	 * Get first inactive gift card. This locks the gift card 
	 * and reserves it for issuing while processing a payment. 
	 *
	 * @return void
	 */	
	public function get_first_inactive_card()
		{
		//$handle = curl_init();
		
		$url = $this->baseUrl . '/get-first-inactive-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array; 
		}
		
	/**
	 * Validate gift card ccv.
	 *
	 * @param [type] $gift_card_id
	 * @param [type] $ccv
	 * @return void
	 */	
	public function validate_gift_card_ccv($gift_card_id, $ccv)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/validate-gift-card-ccv';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id,
			    'ccv' => $ccv
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);


		$array = json_decode($output, true); 
		
		return $array;
		}
		

	/**
	 * Activate a physical gift card.
	 *
	 * @param [type] $gift_card_id
	 * @param [type] $ccv
	 * @param [type] $email
	 * @param [type] $phone
	 * @param [type] $amount
	 * @param [type] $message
	 * @return void
	 */
	public function activate_gift_card($gift_card_id, $ccv, $email, $phone, $amount, $message)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/activate-giftcard';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id,
			    'amount' => $amount
			    ];
		
		if (!empty($email))
			{
			$postData['email'] = $email;
			}
			
		if (!empty($phone))
			{
			$postData['phone'] = $phone;
			}
			
		if (!emply($message))
			{
			$postData['message'] = $message;
			}

		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
	
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}
	
	/**
	 * unlock gift card if credit card processing fails and place it back
	 * into the purchasing pool.
	 *
	 * @param [type] $gift_card_id
	 * @return void
	 */
	public function unlock_gift_card($gift_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/unlock-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
	/**
	 * Find a gift card. 
	 *
	 * @param [type] $gift_card_id
	 * @return void
	 */	
	public function find_gift_card($gift_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/find-gift-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}

	/**
	 * credit gift card.
	 *
	 * @param [type] $gift_card_id
	 * @param [type] $ccv
	 * @param [type] $amount
	 * @return void
	 */
	public function credit_gift_card($gift_card_id, $ccv, $amount)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/credit-gift-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id,
			    'ccv' => $ccv,
			    'amount' => $amount
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}
	/**
	 * Debit amount from a gift card.
	 *
	 * @param [type] $gift_card_id
	 * @param [type] $ccv
	 * @param [type] $amount
	 * @return void
	 */
	public function debit_gift_card($gift_card_id, $ccv, $amount)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/debit-gift-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id,
			    'ccv' => $ccv,
			    'amount' => $amount
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
			

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
		
	/**
	 * get gift card tranaction history.
	 *
	 * @param [type] $gift_card_id
	 * @return void
	 */
	public function get_gift_card_history($gift_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/get-gift-card-history';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'gift_card_id' => $gift_card_id
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
		
	/**
	 * get voucher blocks available for sale.
	 *
	 * @return void
	 */	
	public function get_voucher_block_list()
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/get-voucher-block-list';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId
			    ];
		/*
		$opts = [
			CURLOPT_URL => $url,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $postData,
			CURLOPT_RETURNTRANSFER => true
			];
		
		curl_setopt_array($handle, $opts);
			
		curl_setopt($handle, CURLOPT_URL, $url); 
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		
		$output = curl_exec($handle); 
		
		curl_close($handle);
		*/

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}

	/**
	 * Get voucher info.
	 */
		
	public function get_voucher_info($voucher_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/get-voucher-info';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'voucher_id' => $voucher_id
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}

	/**
	 * get unsold voucher count for block.
	 *
	 * @param [type] $voucher_block_id
	 * @return void
	 */
	public function get_unsold_voucher_count($voucher_block_id)
		{
		$handle = curl_init(); 

		$url = $this->baseUrl . '/get-unsolde-voucher-count'; 

		$postData = [
			'api_key' => $this->apiKey,
			'organization_id' => $this->organizationId,
			'voucher_id' => $voucher_block_id
			];

		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}

	/**
	 * issue a voucher. 
	 *
	 * @param [type] $voucher_block_id
	 * @param [type] $email
	 * @param [type] $phone
	 * @param [type] $recipient_name
	 * @param [type] $recipient_message
	 * @param [type] $qty
	 * @return boolean
	 */	
	public function issue_voucher(	$voucher_block_id, 
					$email, 
					$phone, 
					$recipient_name, 
					$recipient_message = null,
					$qty)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/issue-voucher';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'selected_voucher' => $voucher_block_id
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		
			
		if (!empty($email))
			{
			$postData['email'] = $email;
			}
		
			
		if (!empty($phone))
			{
			$postData['phone'] = $phone;
			}

		if (!empty($recipient_name))
			{
			$postData['recipient_name'] = $recipient_name;	
			}
		
		if (!empty($recipient_message))	
			{
			$postData['recipient_message'] = $recipient_message;
			}

		if (!empty($qty))
			{
			$postData['qty'] = $qty;	
			}
		
		
		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}

	
	/**
	 * redeam a voucher. 
	 *
	 * @param [type] $voucher_id
	 * @param [type] $ccv
	 * @return void
	 */	
	public function redeem_voucher($voucher_id, $ccv)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/redeem-voucer';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'sold_voucher_id' => $voucher_id,
			    'ccv' => $ccv
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}
	/**
	 * issue a points account.
	 *
	 * @param [type] $email
	 * @param [type] $phone
	 * @return boolean
	 */
	public function issue_points_account($email, $phone)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/issue-points-account';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId			    
			    ];
		
		if (isset($phone))
			{
			$postData['phone'] = $phone;
			}
			
		if (isset($email))
			{
			$postData['email'] = $email;
			}
			
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
		
	/**
	 * credit a points account.
	 *
	 * @param [type] $points_account_id
	 * @param [type] $purchase_amount
	 * @return void
	 */
	public function credit_points_account($points_account_id, $purchase_amount)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/points-credit-account';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'points_account_id' => $points_account_id,
			    'purchase_amount' => $purchase_amount
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		
		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
	/**
	 * debit a points account.
	 *
	 * @param [type] $points_account_id
	 * @param [type] $points
	 * @return void
	 */	
	public function debit_points_account($points_account_id, $points)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/points-credit-account';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'points_account_id' => $points_account_id,
			    'points' => $points
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}

		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
	
	/**
	 * get points account balance.
	 *
	 * @param [type] $points_account_id
	 * @return void
	 */
	public function get_points_balance($points_account_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/get-points-balance';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'points_account_id' => $points_account_id
			    ];
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}
	
	/**
	 * issue stamp card.
	 *
	 * @param [type] $email
	 * @param [type] $phone
	 * @return boolean
	 */
	public function issue_stamp_card($email, $phone)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/issue-stamp-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId			    
			    ];
		
		if (isset($phone))
			{
			$postData['phone'] = $phone;
			}
			
		if (isset($email))
			{
			$postData['email'] = $email;
			}
			
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}

	/**
	 * get stamp card info.
	 *
	 * @param [type] $stamp_card_id
	 * @return void
	 */
	public function get_stamp_card_info($stamp_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/get-stamp-card-info';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'stamp_card_id' => $stamp_card_id 
			    ];
		
		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
	
	/**
	 * stamp loyalty card.
	 *
	 * @param [type] $stamp_card_id
	 * @return void
	 */
	public function stamp_loyalty_card($stamp_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/stamp-loyalty-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'stamp_card_id' => $stamp_card_id 
			    ];

		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		

		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);
		
		$array = json_decode($output, true); 
		
		return $array;
		}
		
	/**
	 * redeam loyalty card.
	 *
	 * @param [type] $stamp_card_id
	 * @return void
	 */
	public function redeam_loyalty_card($stamp_card_id)
		{
		$handle = curl_init();
		
		$url = $this->baseUrl . '/stamp-loyalty-card';
		
		$postData = [
			    'api_key' => $this->apiKey,
			    'organization_id' => $this->organizationId,
			    'stamp_card_id' => $stamp_card_id 
			    ];

		if (isset($this->locationId))
			{
			$postData['location_id'] = $this->locationId; 
			}
			
		if (isset($this->cashierNo))
			{
			$postData['cashier_no'] = $this->cashierNo; 
			}
		
		
		$args = [
			'body' => $postData,
			'blocking' => true,
			'httpversion' => '1.0',
			'headers' => [],
			'cookies' => []
			];

		$response = wp_remote_post($url, $args);

		if (is_wp_error($response) || !isset($response['body']))
			{
			return false;	
			}

		$output = wp_remote_retrieve_body($response);

		$array = json_decode($output, true); 
		
		return $array;
		}
	};


