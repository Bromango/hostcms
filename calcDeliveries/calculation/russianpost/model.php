<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

require_once Shop_Delivery_Calculation_Model::getConfig('composerAutoloadPath');

use LapayGroup\RussianPost\Providers\OtpravkaApi;
use LapayGroup\RussianPost\ParcelInfo;

/**
 * Shop_Delivery_Calculation_Russianpost_Model
 *
 * @package HostCMS
 * @subpackage Shop
 * @version 6.x
 * @author Baisungurov Roman
 * @copyright
 */

class Shop_Delivery_Calculation_Russianpost_Model extends Core_Entity
{

	//Хранит авторизационные данные
	protected $auth			= false;
	
	public $fromPostalCode	= false;
	public $toPostalCode	= false;
	public $packageWeight 	= 500;


	// список используемых тарифов

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');

		if ( !empty($_deliveries['russianpost']) ) 
		{
			$this->auth 		  = $_deliveries['russianpost']['authentication'];
			$this->fromPostalCode = $_deliveries['russianpost']['fromPostalCode'];
			$this->packageWeight  = $_deliveries['russianpost']['packageWeight'];
		}
		
		return $this;
	}

	//Устанавливаем почтовый индекс получателя
	public function setToPostalCode($toPostalCode)
	{
		$this->toPostalCode = $toPostalCode;
		return $this;
	}

	public function setFromPostalCode($fromPostalCode)
	{
		$this->fromPostalCode = $fromPostalCode;
		return $this;
	}

	
	//устанавливаем обьем посылки 
	public function setPackageWeight($packageWeight)
	{
		if ($packageWeight > (int)$this->packageWeight) $this->packageWeight = $packageWeight;

		return $this;
	}


	// получаем расчет стоимости доставки
	public function calculationOfCostTariffs() 
	{
		if ($this->toPostalCode && $this->packageWeight) 
		{

			try {
				
				$otpravkaApi = new OtpravkaApi($this->auth);
				
				$_deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');

				$parcelInfo = new ParcelInfo();
				$parcelInfo->setIndexFrom($this->fromPostalCode); // Индекс пункта сдачи из функции $OtpravkaApi->shippingPoints()
				$parcelInfo->setIndexTo($this->toPostalCode);
				$parcelInfo->setMailCategory('ORDINARY'); // https://otpravka.pochta.ru/specification#/enums-base-mail-category
				$parcelInfo->setMailType('POSTAL_PARCEL'); // https://otpravka.pochta.ru/specification#/enums-base-mail-type
				$parcelInfo->setWeight($this->packageWeight);
				$parcelInfo->setFragile(true);

				$tariffInfo = $otpravkaApi->getDeliveryTariff($parcelInfo);


				/*
				 LapayGroup\RussianPost\TariffInfo Object
				 (
				 [totalRate:LapayGroup\RussianPost\TariffInfo:private] => 30658
				 [totalNds:LapayGroup\RussianPost\TariffInfo:private] => 6132
				 [aviaRate:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [aviaNds:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [deliveryMinDays:LapayGroup\RussianPost\TariffInfo:private] => 1
				 [deliveryMaxDays:LapayGroup\RussianPost\TariffInfo:private] => 3
				 [fragileRate:LapayGroup\RussianPost\TariffInfo:p rivate] => 7075
				 [fragileNds:LapayGroup\RussianPost\TariffInfo:private] => 1415
				 [groundRate:LapayGroup\RussianPost\TariffInfo:private] => 30658
				 [groundNds:LapayGroup\RussianPost\TariffInfo:private] => 6132
				 [insuranceRate:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [insuranceNds:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [noticeRate:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [noticeNds:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [oversizeRate:LapayGroup\RussianPost\TariffInfo:private] => 0
				 [oversizeNds:LapayGroup\RussianPost\TariffInfo:private] => 0
				 )
				 */

		        $_aTariffs = false;
		    	if (!empty($tariffInfo->getTotalRate()))
		    	{
		    		$_aTariffs['tariff_name'] 		= 'Почта россии';
		    		$_aTariffs['tariff_description'] 	= 'Вид РПО - Посылка "нестандартная" / Категория РПО - Обыкновенное';
		    		$_aTariffs['tariff_code'] 		= 0;
		    		$_aTariffs['delivery_sum'] 		= $tariffInfo->getTotalRate()/100;
		    		$_aTariffs['deliveryMinDays'] 	= $tariffInfo->getDeliveryMinDays();
		    		$_aTariffs['deliveryMaxDays'] 	= $tariffInfo->getDeliveryMaxDays();
		    	}

				return $_aTariffs;
			}
			catch (\LapayGroup\RussianPost\Exceptions\RussianPostException $e) {
				// Обработка ошибочного ответа от API ПРФ
				return False;
			}
			catch (\Exception $e) {
				// Обработка нештатной ситуации
				return False;
			}

		} else {
			return False;
		}
		
	}


}