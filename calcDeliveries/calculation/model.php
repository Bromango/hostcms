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
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);
	
		return $this;
	}

	/**
	 * Получения массива с параметрами
	 */
	public static function getConfig($name = false) 
	{
		$_config = include('config.php');

		return !empty($_config[$name]) ? $_config[$name] : (!empty($name) ? false : $_config);
	}


	/**
	 * Метод записи в куки как простых значений так и массивов
	 */
	public static function addToCookie($value, $name = false)
	{	
		//Если передали имя и это массив, сохраняем по имени в json формате
		if(!empty($value) && is_array($value) && $name)
		{
			if (Shop_Delivery_Calculation_Model::getCookie($name)) 
			{
				setcookie(strval($name), json_encode(array_merge(Shop_Delivery_Calculation_Model::getCookie($name), $value)), time() + Shop_Delivery_Calculation_Model::getConfig('cookieLifeTime'), '/');

			} else {

				setcookie(strval($name), json_encode($value), time() + Shop_Delivery_Calculation_Model::getConfig('cookieLifeTime'), '/');
			}
			
			return true;
		//Если передали просто массив без имени, пишем в куки по отдельности
		} else if(!empty($value) && is_array($value) && !$name) {

			foreach ($value as $key => $val) 
			{
				Shop_Delivery_Calculation_Model::addToCookie($val, $key);
			}
			return true;

		} else if(!empty($value) && !is_array($value) && $name) {

			if (Shop_Delivery_Calculation_Model::getCookie($name)) 
			{
				setcookie(strval($name), json_encode(array_push(Shop_Delivery_Calculation_Model::getCookie($name), $value)), time() + Shop_Delivery_Calculation_Model::getConfig('cookieLifeTime'), '/');

			} else {

				setcookie(strval($name), $value, time() + Shop_Delivery_Calculation_Model::getConfig('cookieLifeTime'), '/');
			}

			
			return true;

		} else {
			return false;
		}
	}

	/**
	 * метод получения данных из кук. Массивы сначала декодируются, потом возвращаются.
	 */
	public static function getCookie($name = false)
	{	
		if(!$name) return $_COOKIE;
		if($name && !isset($_COOKIE[$name])) return false;
		
		$_aCOOKIE = json_decode($_COOKIE[$name], true);
		if (json_last_error() === JSON_ERROR_NONE) 
		{
			return $_aCOOKIE;
		} else {
			return $_COOKIE[$name];
		}
	}

	/**
	 * Метод записи в сессию
	 * @param $value значение которое пишется в массив на имени $name
	 * если в $value передан массив в сессию пишется все по ключу значению
	 */
	public static function addToSession($value, $name = false)
	{
		if(empty($value)) return false;
		if(!session_id()) session_start();

		if (is_array($value) && !$name) 
		{
			foreach ($value as $key => $val) 
			{
				$_SESSION[strval($key)] = $val;
			}
			//session_write_close();
			return true;

		} else {

			$name = strval($name);
			if(isset($_SESSION[$name])) 
			{
				$_SESSION[$name] = array_merge($_SESSION[$name], $value);

			} else {

				$_SESSION[$name] = $value;
			}
			//session_write_close();
			return true;
		}
	}


	/**
	 * метод отдает геоданные пользователя из сессии / куков / или из сервиса
	 */
	public function getUserGeodata() 
	{	
		//проверяем наличие геоданных в сессии
		if (!empty($_SESSION['location']['postal_code'])) 
		{
			$_location = $_SESSION['location'];
			
			if (empty(Shop_Delivery_Calculation_Model::getCookie('location')))
			{
				$_location['getUserGeodataType'] = 'Cookie';
				Shop_Delivery_Calculation_Model::addToCookie($_location, 'location');
			}

			$_location['getUserGeodataType'] = 'SESSION';

			return $_location;
		} 
		//проверяем наличие геоданных пользователя в куках
		elseif (!empty(Shop_Delivery_Calculation_Model::getCookie('location'))) 
		{
			$_location = Shop_Delivery_Calculation_Model::getCookie('location');

			//Добавляем из Кук в Сессию
			$_location['getUserGeodataType']= 'SESSION';
			Shop_Delivery_Calculation_Model::addToSession($_location, 'location');

			$_location['getUserGeodataType']= 'Cookie';

			return $_location;
		}
		else //получаем геоданные по ip адресу 
		{
			$_oShop_Delivery_Calculation_Geodata = Core_Entity::factory('Shop_Delivery_Calculation_Geodata', 0);

			$_location = $_oShop_Delivery_Calculation_Geodata->getGeodata($_SERVER['REMOTE_ADDR']);

			if( empty($_location) ) return false;

			$_location['getUserGeodataType']= 'SESSION';
			Shop_Delivery_Calculation_Model::addToSession($_location, 'location');

			$_location['getUserGeodataType']= 'Cookie';
			Shop_Delivery_Calculation_Model::addToCookie($_location, 'location');
	
			$_location['getUserGeodataType']= 'CURL';
			return $_location;
		}
	}

	/**
	 * Обновляет данные location по id города HostCms из BD c почтовыми индексами
	 */
	public function updateUserGeodata($locationCityId = false) 
	{
		if ( !$locationCityId ) return false;

		if ( Shop_Delivery_Calculation_Model::getConfig('fromDatabase') ) 
		{
			$oCore_QueryBuilder_Select = Core_QueryBuilder::select('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id')
				->from(Shop_Delivery_Calculation_Model::getConfig('dbTableName'))
				->where('shop_country_id', '=', 175)
				->where('id', '=', $locationCityId); 

			$aRows = $oCore_QueryBuilder_Select->execute()->asAssoc()->result();

			if ( empty($aRows) ) return false; 
			
			//Добавляем в сессию и куки
			$this->addToSession($aRows[0], 'location');
			$this->addToCookie($aRows[0], 'location');

			return $aRows[0];
		}
		
		return false;
	} 

	//Удаляет переменную сессии по переданному имени
	public static function clearSession($name = false) 
	{
		if(!$name) return false;

		unset($_SESSION[$name]);
	
		return true;
	}
	//Удаляет переменную куков по переданному имени
	public static function clearCookie($name = false) 
	{
		if(!$name) return false;

		unset($_aCOOKIE[$name]);
	
		return true;
	}

	
	//Интеграция и расчет стоимости доставкой сдеком 
	public function getPriceSdekDeliveryByPostcode($postcode = false, $Weight = false) 
	{	
		$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');
		if (!$_deliveries['sdek']['active']) return false;
		
		if (!$postcode) return false;

		$_oDelivery_Calculation_Sdek = Core_Entity::factory('Shop_Delivery_Calculation_Sdek')
			->setToPostalCode($postcode)
			->setPackageWeight($Weight ? $Weight : $_deliveries['sdek']['packageWeight'])
			->setTariffs($_deliveries['sdek']['tariffs']);

		return $_oDelivery_Calculation_Sdek->calculationOfCostTariffs();
	}
	public function getPriceSdekDeliveryByPvzId($pvzid = false, $Weight = false) 
	{	
		$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');
		if (!$_deliveries['sdek']['active']) return false;
		
		if (!$pvzid) return false;

		$_oDelivery_Calculation_Sdek = Core_Entity::factory('Shop_Delivery_Calculation_Sdek')
			->setToPvzId($pvzid)
			->setPackageWeight($Weight ? $Weight : $_deliveries['sdek']['packageWeight'])
			->setTariffs($_deliveries['sdek']['tariffs']);

		return $_oDelivery_Calculation_Sdek->calculationOfCostTariffs();
	}

	//Интеграция и расчет стоимости Почтой России
	public function getPriceRussianpostByPostcode($postcode = false, $Weight = 500) 
	{	
		$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');
		if (!$_deliveries['russianpost']['active']) return false;

		if (!$postcode) return false;

		$_oDelivery_Calculation_RussianPost = Core_Entity::factory('Shop_Delivery_Calculation_Russianpost')
			->setToPostalCode($postcode)
			->setPackageWeight($Weight ? $Weight : $_deliveries['russianpost']['packageWeight']);

		return $_oDelivery_Calculation_RussianPost->calculationOfCostTariffs();
	}

	//Интеграция и расчет стоимости Боксбери
	public function getPriceBoxberryByPvzId($pvzid = false, $Weight = false, $amount = false) 
	{	
		$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');
		if (!$_deliveries['boxberry']['active']) return false;

		if (!$pvzid) return false;

		$_oDelivery_Calculation_Boxberry = Core_Entity::factory('Shop_Delivery_Calculation_Boxberry')
			->setToPvzId($pvzid)
			->setPackageWeight($Weight ? $Weight : $_deliveries['boxberry']['packageWeight']);

		if ($amount) $_oDelivery_Calculation_Boxberry->setAmount($amount);

		return $_oDelivery_Calculation_Boxberry->calculationOfCostTariffs();
	}


	/**
	 * --Принимает почтовый индекс, отдает идентификаторы города из БД доставок--
	 * Так же метод автоматически пишет (и обновляет) данные в сессии и куках
	 */
	public function getDeliveryIdFromDB( $postcode = false, $AreaId = 0, $CityId = 0 ) 
	{
		if ( !Shop_Delivery_Calculation_Model::getConfig('fromDatabase') ) return false;

		// Поиск почтового индекса и boxberry_id по региону и городу 
		if ( !$postcode && $AreaId && $CityId ) 
		{
			// Название таблицы указанно в файле конфиге
			// Сначала ищем точное совпадение по региону и городу 
			$oCore_QueryBuilder_Select = Core_QueryBuilder::select('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id')
				->from(Shop_Delivery_Calculation_Model::getConfig('dbTableName'))
				->where('shop_country_id', '=', 175)
				->where('shop_country_location_id', '=', $AreaId)
				->where('id', '=', $CityId); 

			$aRows = $oCore_QueryBuilder_Select->execute()->asAssoc()->result();

			if (count($aRows)) 
			{
				//Добавляем в сессию и куки
				$this->addToSession($aRows[0], 'location');
				$this->addToCookie($aRows[0], 'location');

				return $aRows[0];

			} else { 

				//Если не нашли совпадения с полученным почтовым индексом чистим данные доставок сессии и кук
				$_keys_to_delete = array('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id');
				//удаляем из сессии
				if(isset($_SESSION['location'])) 
				{
					foreach ($_SESSION['location'] as $key => $value) 
					{
						if (in_array($key, $_keys_to_delete)) unset($_SESSION['location'][$key]);
					}
				}

				//удаляем из cookie
				if($this->getCookie('location')) 
				{
					$_aCOOKIE = $this->getCookie('location');
					foreach ($_aCOOKIE as $key => $value) 
					{
						if (in_array($key, $_keys_to_delete)) unset($_aCOOKIE[$key]);
					}
					$this->addToCookie($_aCOOKIE, 'location');
				}

				return false;
			}

		// Поиск id региона и id города по почтовому индексу
		} else {

			// Название таблицы указанно в файле конфиге
			// Сначала ищем точное совпадение с полученным почтовым индексом
			$oCore_QueryBuilder_Select = Core_QueryBuilder::select('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id')
				->from(Shop_Delivery_Calculation_Model::getConfig('dbTableName'))
				->where('shop_country_id', '=', 175)
				->where('postal_code', '=', $postcode); 

			$aRows = $oCore_QueryBuilder_Select->execute()->asAssoc()->result();

			if (count($aRows)) 
			{
				//Добавляем в сессию и куки
				$this->addToSession($aRows[0], 'location');
				$this->addToCookie($aRows[0], 'location');

				return $aRows[0];

			} else {

				//Ищем максимально похожее совпадение (это связанно большим количеством почтовых индексов в городах)
				$oCore_QueryBuilder_Select = Core_QueryBuilder::select('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id')
					->from(Shop_Delivery_Calculation_Model::getConfig('dbTableName'))
					->where('shop_country_id', '=', 175) //175 - id страна Россия
					->where('postal_code', 'LIKE', substr($postcode, 0, 5).'%');

				$aRows = $oCore_QueryBuilder_Select->execute()->asAssoc()->result();

				if (count($aRows)) 
				{
					//Добавляем в сессию и куки
					$this->addToSession($aRows[0], 'location');
					$this->addToCookie($aRows[0], 'location');

					return $aRows[0];

				} else {

					//Если не нашли совпадения с полученным почтовым индексом чистим данные доставок сессии и кук
					$_keys_to_delete = array('shop_country_location_id', 'id', 'postal_code', 'sdek_id', 'boxberry_id', 'dpd_id', 'region_kladr_id', 'region_fias_id');
					//удаляем из сессии
					if(isset($_SESSION['location'])) 
					{
						foreach ($_SESSION['location'] as $key => $value) 
						{
							if (in_array($key, $_keys_to_delete)) unset($_SESSION['location'][$key]);
						}
					}

					//удаляем из cookie
					if($this->getCookie('location')) 
					{
						$_aCOOKIE = $this->getCookie('location');
						foreach ($_aCOOKIE as $key => $value) 
						{
							if (in_array($key, $_keys_to_delete)) unset($_aCOOKIE[$key]);
						}
						$this->addToCookie($_aCOOKIE, 'location');
					}

					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Формирует и отдает готовый XML на основе всех собранных данных в $_SESSION['location']
	 */
	public function getXMLUserGeodata()
	{
		//если в сессии еже не доздан массив с локальными данным пользователя, создаем
		if( empty($_SESSION['location']) ) $this->getUserGeodata();

		//Формируем и отдаем XML из массива 
		$o_XmlLocation = Core::factory('Core_Xml_Entity')->name('location');
		foreach ($_SESSION['location'] as $key => $value) 
		{
			$o_XmlLocation->addEntity(
				Core::factory('Core_Xml_Entity')
					->name($key)
					->value($value)
			);
		}

		return $o_XmlLocation;
	}

	/**
	 * Вывод доставок в xml из session 
	 * если нет в session, получить доставки потом вывести в xml  
	 */
	public function getDeliveries($weight = false, $amount = 0, $AreaId = false, $CityId = false) 
	{
		$_config = Shop_Delivery_Calculation_Model::getConfig('deliveries');

		if( empty($weight) ) $weight = $_config['sdek']['packageWeight'];
		if( empty($amount) ) $amount = $_config['boxberry']['amount'];

		/* -------- Список доставок отталкиваясь от id региона и id города-----------------------*/
		if ( $AreaId  &&  $CityId ) 
		{
			//Получаем список активных доставок
			$_Shop_Deliveries = $this->getStandardDeliveryList($AreaId, $CityId, $weight, $amount);

			// Ищем по id региона и id города соответствующие почтовый индекс и boxberry_id
			$_locations = $this->getDeliveryIdFromDB(false, $AreaId, $CityId);
			if (!empty($_locations['postal_code']) && !empty($_Shop_Deliveries)) 
			{
				// Проверяем есть ли расчитанные доставки в сессии
				if( !empty($_SESSION['Deliveries_'.$_locations['postal_code']]) ) return $_SESSION['Deliveries_'.$_locations['postal_code']];

				// Обновляем доставки 
				$_Shop_Deliveries = $this->updateTheCostOfDeliveries($_Shop_Deliveries, $_locations, $weight, $amount);

			}
	
			// отдаем массив с доставкми
			return $_Shop_Deliveries;
		}

		/* --------  Если нет id региона и id города пытаемся просчитать доставку по почтовому индексу  ----------------------*/ 
		if ( !$AreaId  &&  !$CityId ) 
		{
			//Читаем данные местоположения пользователя (почтовый индекс, город)
			$_data = $this->getUserGeodata();
			if( empty($_data) ) return false;

			// Проверяем есть ли расчитанные доставки в сессии
			if( !empty($_data['postal_code']) && !empty($_SESSION['Deliveries_'.$_data['postal_code']]) ) return $_SESSION['Deliveries_'.$_data['postal_code']];

			// Ищем по почтовому индексу id региона и id города
			$_locations = $this->getDeliveryIdFromDB($_data['postal_code']);
			if (!empty($_locations['shop_country_location_id']) && !empty($_locations['id'])) 
			{
				//Получаем список активных доставок
				$_Shop_Deliveries = $this->getStandardDeliveryList($_locations['shop_country_location_id'], $_locations['id'], $weight, $amount);

				if (!empty($_locations['postal_code']) && !empty($_Shop_Deliveries)) 
				{
					// Обновляем доставки 
					$_Shop_Deliveries = $this->updateTheCostOfDeliveries($_Shop_Deliveries, $_locations, $weight, $amount);
				}

				// отдаем массив с доставкми
				return $_Shop_Deliveries;
			}
		}
		
		return false;
	}

	/**
	 * Обновляет стандартные (статичные) стоимости доставок на актуальные, расчитаные онлайн
	 */
	public function updateTheCostOfDeliveries($_Shop_Deliveries, $_locations, $weight, $amount)
	{
		//Идем циклом по всем активным доставкам из конфига 
		$aData = Shop_Delivery_Calculation_Model::getConfig('deliveries');
		foreach ($aData as $_dName => $_delivery) 
		{
			if($_delivery['active'] && isset($_Shop_Deliveries[$_delivery['id'][0]]) || $_delivery['active'] && isset($_Shop_Deliveries[$_delivery['id'][1]]))
			{
				//Расчитываем доставкиу сдэком и заменяем цену в массиве, который после пойдет в xml
				if ($_dName == 'sdek' && !empty($_locations['postal_code']) || $_dName == 'sdek' && !empty($_locations['sdek_id'])) 
				{
					//Расчитываем доставку сдеком и добавляем результат в массив
					$_tmp_sdek = $this->getPriceSdekDeliveryByPostcode($_locations['postal_code'], $weight);
					// Если не получилость расчитать по почтовому индексу попробуем по id sdek
					if (empty($_tmp_sdek) && !empty($_locations['sdek_id']))  $_tmp_sdek = $this->getPriceSdekDeliveryByPvzId($_locations['sdek_id'], $weight);
					
					if(!empty($_tmp_sdek)) 
					{
						foreach ($_tmp_sdek as $_val) 
						{
							$_Shop_Deliveries[$_delivery[$_val['tariff_code']]]['deliveryMinDays'] 	= $_val['deliveryMinDays'];
							$_Shop_Deliveries[$_delivery[$_val['tariff_code']]]['deliveryMaxDays'] 	= $_val['deliveryMaxDays'];
							$_Shop_Deliveries[$_delivery[$_val['tariff_code']]]['price'] 			= $_val['delivery_sum'];
							$_Shop_Deliveries[$_delivery[$_val['tariff_code']]]['calculation'] 		= true;
						}

					} 					
				}

				//Расчитываем доставкиу Почтой России и заменяем цену в массиве, который после пойдет в xml
				if ($_dName == 'russianpost' && !empty($_locations['postal_code'])) 
				{
					$_tmp_postal = $this->getPriceRussianpostByPostcode($_locations['postal_code'], $weight);

					if(!empty($_tmp_postal)) 
					{
						//100% предоплаты 
						$_Shop_Deliveries[$_delivery['prepayment']]['deliveryMinDays'] 	= $_tmp_postal['deliveryMinDays'];
						$_Shop_Deliveries[$_delivery['prepayment']]['deliveryMaxDays'] 	= $_tmp_postal['deliveryMaxDays'];
						$_Shop_Deliveries[$_delivery['prepayment']]['price'] 			= round($_tmp_postal['delivery_sum'], 0);
						$_Shop_Deliveries[$_delivery['prepayment']]['calculation'] 		= true;

						//Для наложного платежа с наценкой 
						$_fullPrice = round($_tmp_postal['delivery_sum'] + ($_tmp_postal['delivery_sum'] * $_delivery['extra_charge'] / 100) + 50, 0);
						$_Shop_Deliveries[$_delivery['cash_on_delivery']]['deliveryMinDays'] 	= $_tmp_postal['deliveryMinDays'];
						$_Shop_Deliveries[$_delivery['cash_on_delivery']]['deliveryMaxDays'] 	= $_tmp_postal['deliveryMaxDays'];
						$_Shop_Deliveries[$_delivery['cash_on_delivery']]['price'] 				= $_fullPrice;
						$_Shop_Deliveries[$_delivery['cash_on_delivery']]['calculation'] 		= true;


					} else {

						$_Shop_Deliveries[$_delivery['prepayment']]['calculation'] 		= false;
						$_Shop_Deliveries[$_delivery['cash_on_delivery']]['calculation']= false;
					}
				}

				//Расчитываем доставкиу БоксБери и заменяем цену в массиве, который после пойдет в xml
				if ($_dName == 'boxberry' && !empty($_locations['boxberry_id'])) 
				{
					$_tmp_postal = $this->getPriceBoxberryByPvzId($_locations['boxberry_id'], $weight, $amount);
					
					if (!empty( $_tmp_postal )) 
					{
						$_fullPrice = round($_tmp_postal['delivery_sum'] + ($_tmp_postal['delivery_sum'] * $_delivery['extra_charge'] / 100), 0);
						$_Shop_Deliveries[$_delivery['pickup']]['deliveryMinDays'] 	= $_tmp_postal['deliveryMinDays'];
						$_Shop_Deliveries[$_delivery['pickup']]['deliveryMaxDays'] 	= $_tmp_postal['deliveryMaxDays'];
						$_Shop_Deliveries[$_delivery['pickup']]['price'] 			= $_fullPrice;
						$_Shop_Deliveries[$_delivery['pickup']]['calculation'] 		= true;

					} else {
						$_Shop_Deliveries[$_delivery['pickup']]['calculation'] = false; 
					}
				}
			} 
		}

		if (!empty($_locations['postal_code'])) Shop_Delivery_Calculation_Model::addToSession($_Shop_Deliveries, 'Deliveries_'.$_locations['postal_code']);	

		return $_Shop_Deliveries; 
	}

	/**
	 * Возвращает стандартный список активных в системе доставок
	 */
	public function getStandardDeliveryList($AreaId, $CityId, $weight, $amount) 
	{
		//Получаем список активных доставок
		$oShop = Core_Entity::factory('Shop', Shop_Delivery_Calculation_Model::getConfig('HostCmsShopId'));
		$Shop_Cart_Controller_Onestep = new Shop_Cart_Controller_Onestep($oShop);

		$aDeliveries = $Shop_Cart_Controller_Onestep->showDelivery(175, (int) $AreaId, (int) $CityId, 0, $weight, $amount);
		
		if(count($aDeliveries)) 
		{
			//Для удобства делаем ключами массива идентификаторы доставок hostcms 
			$_tmp = array();
			foreach ($aDeliveries as $value) $_tmp[$value['id']] = $value;
			return $_tmp;	
		}
		
		return $aDeliveries;	
	}

	/**
	 * Формирует и отдает готовый XML на основе всех собранных доставок
	 */
	public function getXMLDeliveries($_Array)
	{
		//Формируем и отдаем XML из массива 
		$o_XmlDeliveries = Core::factory('Core_Xml_Entity')->name('deliveries');
		foreach ($_Array as $_delivery) 
		{
			$o_XmlDelivery = Core::factory('Core_Xml_Entity')->name('delivery')->addAttribute('id', $_delivery['id']);

			// Помечаем ранее выбранную пользователем службу доставки 
			if(!empty($_SESSION['location']['delivery']) && $_delivery['shop_delivery_condition_id'] == $_SESSION['location']['delivery']) 
			{
				$o_XmlDelivery->addAttribute('active', 1);
			}

			// Помечаем службу доставки у которой есть возможность показа виджета 
			$_Deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');
			if($_delivery['id'] == $_Deliveries['sdek']['pickup']) 
			{
				$o_XmlDelivery->addAttribute('widget', 'sdek_widget');
			}
			if($_delivery['id'] == $_Deliveries['boxberry']['pickup']) 
			{
				$o_XmlDelivery->addAttribute('widget', 'boxberry_widget');
			}

			foreach ($_delivery as $key => $value) 
			{
				$o_XmlDelivery->addEntity(
					Core::factory('Core_Xml_Entity')
						->name($key)
						->value($value)
				);
			}
			$o_XmlDeliveries->addEntity($o_XmlDelivery);
		}

		return $o_XmlDeliveries;
	}

	/**
	 * Формирует и отдает готовый XML список всех регионов и городов с отмеченным городом пользователя
	 */
	public function getXMLListOfCities()
	{
		//Читаем данные местоположения пользователя (почтовый индекс, город)
		$_data = $this->getUserGeodata();

		//Общий корень XML с городами и регионами
		$o_XmlCountry_Locations = Core::factory('Core_Xml_Entity')->name('Country_Locations');

		//Список регионов с выделенным регионом пользователя 
		$o_XmlAreas = Core::factory('Core_Xml_Entity')->name('Location_areas');
		$oShop_Country_Location = Core_Entity::factory('Shop_Country_Location');
		$oShop_Country_Location
			->queryBuilder()
			->where('shop_country_id', '=', 175);

		$aObjects = $oShop_Country_Location->findAll();
		foreach ($aObjects as $Object)
		{
			$o_XmlArea = Core::factory('Core_Xml_Entity')
				->name('area')
				->addAttribute('id', $Object->id);
			
			if (!empty($_data['shop_country_location_id']) && (int)$_data['shop_country_location_id'] == $Object->id ) $o_XmlArea->addAttribute('active', 1);
			
			$o_XmlArea->value($Object->name);
				
			$o_XmlAreas->addEntity($o_XmlArea);
		}
		$o_XmlCountry_Locations->addEntity($o_XmlAreas);

		//Если не id  города выводим просто список регионов
		if ( empty($_data['id']) && empty($_SESSION['location']['shop_country_location_id']) ) return $o_XmlCountry_Locations; 

		if ( empty($_data['shop_country_location_id']) && !empty($_SESSION['location']['shop_country_location_id']) ) $_data = $_SESSION['location'];

		//Список городов региона польователя 
		$o_XmlCities = Core::factory('Core_Xml_Entity')->name('Location_cities');
		$oShop_Country_Location_City = Core_Entity::factory('Shop_Country_Location_City');
		$oShop_Country_Location_City
			->queryBuilder()
			->where('shop_country_location_id', '=', (int)$_data['shop_country_location_id']);
			
		$aObjects = $oShop_Country_Location_City->findAll();
	    foreach ($aObjects as $Object)
		{
			$o_XmlCity = Core::factory('Core_Xml_Entity')
				->name('city')
				->addAttribute('id', $Object->id);
			
			if (!empty($_data['shop_country_location_id']) && (int)$_data['id'] == $Object->id ) $o_XmlCity->addAttribute('active', 1);
			
			$o_XmlCity->value($Object->name);
				
			$o_XmlCities->addEntity($o_XmlCity);
		}
		$o_XmlCountry_Locations->addEntity($o_XmlCities);


		return $o_XmlCountry_Locations; 

		/*
		<Country_Locations>
			<Location_areas>
				<area id="1" active="1">Московская обл.</area>
				<area id="2">Ленинградская обл.</area>
			</Location_areas>
			<Location_cities>
				<city id="1629" active="1">Азов</city>
				<city id="1630">Аксай</city>
			</Location_cities>
		</Country_Locations>		
		*/
	}





	/**
	 * Определение объема веса и сыммы товаров
	 */
	public static function getWeightVolumeOfProducts($aShop_Cart) 
	{
		$iTotal = 0;
		$iTotalVolume = 0;
		$iTotalWeight = 0;
		if (count($aShop_Cart) || Core_Array::get($_SESSION, 'last_order_id'))
		{
			foreach ($aShop_Cart as $Shop_Cart) 
			{
				$oShop_Item = Core_Entity::factory("Shop_Item", $Shop_Cart->shop_item_id);
				$aShop_Item_Prices = $oShop_Item->getPrices();
				$iTotal += $aShop_Item_Prices['price'] * $Shop_Cart->quantity;

				$w = $oShop_Item->width; 
				$h = $oShop_Item->height;
				$l = $oShop_Item->length;
				$Weight = $oShop_Item->weight;

				if ($w && $h && $l) 
				{
					$iTotalVolume += ($w*$h*$l);
				}
				if ($Weight) 
				{
					$iTotalWeight += $Weight;
				}
				
			}
		}
		if ($iTotalVolume) 
		{
			$aTotal['volume'] = $iTotalVolume = intval(pow($iTotalVolume, 1/3));
		}
		$aTotal['weight'] = $iTotalWeight;
		$aTotal['amount'] = $iTotal;

		return $aTotal;
	}
}