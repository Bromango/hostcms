<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Shop_Delivery_Calculation_Model
 *
 * @package HostCMS
 * @subpackage Shop
 * @version 6.x
 * @author Baisungurov Roman
 * @copyright
 */

class Shop_Delivery_Calculation_Model extends Core_Entity
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
	 * метод отдает геоданные пользователя из сессии / куков / или из сервиса
	 */
	public function getUserGeodata() 
	{	
		//проверяем наличие геоданных в сессии
		if (isset($_SESSION['location']['postal_code']) && isset($_SESSION['location']['city_name'])) 
		{
			$_location['postal_code'] 		= $_SESSION['location']['postal_code'];
			$_location['city_name'] 		= $_SESSION['location']['city_name'];
			$_location['getUserGeodataType']= 'SESSION';

			return $_location;
		} 
		//если нет, проверяем наличие геоданных пользователя в куках
		elseif ($this->getCookie('postal_code') && $this->getCookie('city_name')) 
		{
			$_location['postal_code'] 		= $this->getCookie('postal_code');
			$_location['city_name'] 		= $this->getCookie('city_name');
			$_location['getUserGeodataType']= 'Cookie';

			return $_location;
		}
		else // если нет, получаем геоданные по ip адресу 
		{
			$_oShop_Delivery_Calculation_Geodata = Core_Entity::factory('Shop_Delivery_Calculation_Geodata', 0);

			return $_oShop_Delivery_Calculation_Geodata->getGeodata($_SERVER['REMOTE_ADDR']);
		}
	}

	
	//получение и запись в сессию идентификаторов города и региона hostcms 

	//Интеграция и расчет стоимости доставкой сдеком 
	public function getPriceSdekDeliveryByPostcode($postcode = false) 
	{
		if (!$postcode) return false;

		$_oDelivery_Calculation_Sdek = Core_Entity::factory('Shop_Delivery_Calculation_Sdek')
			->setToPostalCode($postcode)
			->setPackageWeight(500)
			->setTariffs(array(136, 137));

		return $_oDelivery_Calculation_Sdek->calculationOfCostTariffs();

	}

	//Интеграция и расчет стоимости Почтой России
	public function getPriceRussianpostByPostcode($postcode = false, $Weight = 500) 
	{
		if (!$postcode) return false;

		$_oDelivery_Calculation_RussianPost = Core_Entity::factory('Shop_Delivery_Calculation_Russianpost')
			->setToPostalCode($postcode)
			->setPackageWeight($Weight);

		return $_oDelivery_Calculation_RussianPost->calculationOfCostTariffs();

	}

	//Интеграция и расчет стоимости Боксбери

	//Интеграция и расчет стоимости озон



	/**
	 * В сквозном Получаем местоположение пользователя 
	 * 
	 */

}