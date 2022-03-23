<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

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
	 * Хранит авторизационный токен для доступа к API - suggestions.dadata.ru
	 */
	protected $token_dadata = "b44617a9c86ecdd65fbd1b095d381c2c89f6e8d6";

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUserCurrent = Core_Entity::factory('User', 0)->getCurrent();
			$this->_preloadValues['user_id'] = is_null($oUserCurrent) ? 0 : $oUserCurrent->id;
		}

		return $this;
	}

	public function getConfig() 
	{
	    return include(dirname(__DIR__) . '/config.php');
	}

	/**
	 * Метод записи в куки 
	 */
	public function addToCookie($value, $name = false)
	{
		if (is_array($value)) 
		{
			foreach ($value as $key => $val) 
			{
				if (is_array($val)) 
				{ 
					$this->addToCookie($val, $key);

				} else {

					setcookie(strval($key), $val);
				}
			}
		} else {

			setcookie(strval($name), $value, time() + 3600*720, '/',);
		}
		
	}

	/**
	 * метод получения данных из кук
	 */
	public function getCookie($name)
	{
		return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : false ) ;
	}

	/**
	 * Метод записи в сессию
	 * @param $value значение которое пишется в массив на имени $name
	 * если в $value передан массив в сессию пишется все по ключу значению
	 */
	public function addToSession($value, $name = false)
	{
		if (!session_id()) session_start();

		if (is_array($value) && !$name) 
		{
			foreach ($value as $key => $val) 
			{
				$_SESSION[strval($key)] = $val;
			}
			return true;
		}
		elseif (!empty($value))
		{
			$_SESSION[strval($name)] = $value;
			return true;
		} 
		else 
		{
			return false;
		}
		$_SESSION[strval($name)] = $value;
		session_write_close();
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

		$this->addToSession($_location, 'location');
		$this->addToCookie($_location);

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
		$_config = $this->getConfig();
		$_token_dadata = $_config['geodata']['dadata'][rand(0, count($_config['geodata']['dadata'])-1)]['token'];

		$ch = curl_init('https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address?ip=' . $ip );
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Token ' . $_token_dadata ));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$res = curl_exec($ch);
		if (curl_error($ch) || $res === false || empty($res)) return false;
		curl_close($ch);

		$data = json_decode($res, true);

		print_r($data);
		//Если получили город и почтовый индекс города, собираем в массив самые нужные нам данные
		if (isset($data['location']['data']['postal_code'])) 
		{
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

		return false;
	}

}