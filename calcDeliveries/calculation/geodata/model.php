<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

require_once Shop_Delivery_Calculation_Model::getConfig('composerAutoloadPath');


/**
 * Shop_Delivery_Calculation_Geodata_Model
 *
 * @package HostCMS
 * @subpackage Shop
 * @version 6.x
 * @author Baisungurov Roman
 * @copyright
 */

class Shop_Delivery_Calculation_Geodata_Model extends Core_Entity
{
	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		return $this;
	}


	/**
	 * 
	 */
	public function getGeoData($ip = false)
	{
		if (!$ip) return false;

		$_location = $this->getGeodataByDadata($ip);

		empty($_location) && $_location = $this->getGeodataByIpApi($ip);

		//empty($_location) && $_location = $this->getGeodataByGeoIp2($ip);

		return $_location;
	}


	/**
	 * Отдает местоположение пользователя, название города, почтовый индекс и т.д. через сервис ip-api.com
	 * @param $ip - ip адрес пользователя
	 */
	public static function getGeodataByIpApi($ip = false) 
	{
		if (empty($ip)) return false;

		$ch = curl_init('http://ip-api.com/php/' . $ip . '?lang=ru');
		//curl_setopt($ch, CURLOPT_HTTPHEADER); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);

		if (curl_error($ch) || $res === false || empty($res)) return false;

		curl_close($ch);
		
		$data = unserialize($res);

		//Если получили город и почтовый индекс города, собираем в массив самые нужные нам данные
		if (isset($data['zip']) && $data['status'] == 'success') 
		{
			$_location['postal_code'] 		= $data['zip'];
			$_location['city_name'] 		= $data['city'];
			$_location['geo_lat']	 		= $data['lat'];
            $_location['geo_lon']	 		= $data['lon'];
            $_location['region_type']	 	= $data['region'];
            $_location['region']	 		= $data['regionName'];

            return $_location;
		}

		return false;
	}


	/**
	 * Отдает местоположение пользователя, название города, почтовый индекс и т.д. через сервис dadata.ru
	 * @param $ip - ip адрес пользователя
	 */
	public function getGeodataByDadata($ip = false) 
	{
		if (empty($ip)) return false;

		/**
		 * Хранит авторизационный токен для доступа к API - suggestions.dadata.ru
		 */
		$_config = Shop_Delivery_Calculation_Model::getConfig('geodata');
		$_token_dadata = $_config['dadata']['token'];

		$ch = curl_init('https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=' . $ip );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Token ' . $_token_dadata ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		if (curl_error($ch) || $res === false || empty($res)) return false;
		curl_close($ch);

		$data = json_decode($res, true);

		if (empty($data['location']['data']['postal_code'])) return false;

		//Если получили город и почтовый индекс города, собираем в массив самые нужные нам данные
		$_location['postal_code'] 		= $data['location']['data']['postal_code'];
		$_location['city_name'] 		= $data['location']['data']['city'];
		$_location['geo_lat']	 		= $data['location']['data']['geo_lat'];
		$_location['geo_lon']	 		= $data['location']['data']['geo_lon'];
		$_location['region_with_type']	= $data['location']['data']['region_with_type'];
		$_location['region_type']	 	= $data['location']['data']['region_type'];
		$_location['region_type_full']	= $data['location']['data']['region_type_full'];
		$_location['region']	 		= $data['location']['data']['region'];


		return $_location;

	}

}