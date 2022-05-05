<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

require_once Shop_Delivery_Calculation_Model::getConfig('composerAutoloadPath');

/**
 * Shop_Delivery_Calculation_Sdek_Model
 *
 * @package HostCMS
 * @subpackage Shop
 * @version 6.x
 * @author Baisungurov Roman
 * @copyright
 */

class Shop_Delivery_Calculation_Sdek_Model extends Core_Entity
{
	/**
	 * Хранит авторизационные данные
	 */
	protected $account 		= false;
	protected $secure 		= false;
	public $tariffs 		= false;
	public $toPostalCode	= false;
	public $toPvzId			= false;
	public $fromPostalCode 	= false;
	public $packageWeight 	= false;
	public $fromSdekId 		= false;


	// список используемых тарифов

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		$_SDEK = Shop_Delivery_Calculation_Model::getConfig('deliveries');

		$this->account 			= $_SDEK['sdek']['account'];
		$this->secure 			= $_SDEK['sdek']['secure'];
		$this->tariffs 			= $_SDEK['sdek']['tariffs']; //склад-склад / склад-дверь
		$this->fromPostalCode 	= $_SDEK['sdek']['fromPostalCode'];
		$this->packageWeight 	= $_SDEK['sdek']['packageWeight'];
		$this->fromSdekId 		= $_SDEK['sdek']['fromSdekId'];

		return $this;
	}

	//Устанавливаем почтовый индекс получателя
	public function setToPostalCode($toPostalCode)
	{
		$this->toPostalCode = $toPostalCode;
		
		return $this;
	}

	//Устанавливаем почтовый индекс получателя
	public function setToPvzId($toPvzId)
	{
		$this->toPvzId = $toPvzId;
		
		return $this;
	}

	//устанавливаем почтовый индек отправителя 
	public function setFromPostalCode($fromPostalCode)
	{
		$this->fromPostalCode = $fromPostalCode;

		return $this;
	}

	//устанавливаем нужные тарифы 
	public function setTariffs($tariffs)
	{
		$this->tariffs = $tariffs;

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
		if ($this->toPostalCode) 
		{
			try {
				$cdek_client = new \AntistressStore\CdekSDK2\CdekClientV2($this->account, $this->secure);
				//$cdek_client 	= new \AntistressStore\CdekSDK2\CdekClientV2('TEST');
				$tariff 		= new \AntistressStore\CdekSDK2\Entity\Requests\Tariff();
				$Location 		= new \AntistressStore\CdekSDK2\Entity\Requests\Location();
				$tariff 
					->setToLocation($Location->withPostalCode($this->toPostalCode))
					->setFromLocation($Location->withPostalCode($this->fromPostalCode))
					->setType(1) // (1 - "интернет-магазин", 2 - "доставка")
					->setPackageWeight($this->packageWeight)
					->addServices(['PART_DELIV']) //список сервис кодов -  \AntistressStore\CdekSDK2\Constants - SERVICE_CODES
						;

				$tariffList = $cdek_client->calculateTariffList($tariff);
				$_aTariffs = false;
				foreach ($tariffList as $tariff) 
				{	
					if (in_array($tariff->getTariffCode(), $this->tariffs)) 
					{
						$_aTariffs[$tariff->getTariffCode()]['tariff_name'] 		= $tariff->getTariffName();
						$_aTariffs[$tariff->getTariffCode()]['tariff_description'] 	= $tariff->getTariffDescription();
						$_aTariffs[$tariff->getTariffCode()]['tariff_code'] 		= $tariff->getTariffCode();
						$_aTariffs[$tariff->getTariffCode()]['delivery_sum'] 		= round($tariff->getDeliverySum());
						$_aTariffs[$tariff->getTariffCode()]['deliveryMinDays'] 	= $tariff->getPeriodMin();
						$_aTariffs[$tariff->getTariffCode()]['deliveryMaxDays'] 	= $tariff->getPeriodMax();
					}
				}

				return $_aTariffs;

			} catch (\Throwable $th) {
				
				return False;
			}
			

		} 
		
		if ($this->toPvzId) 
		{
			try {
				$cdek_client = new \AntistressStore\CdekSDK2\CdekClientV2($this->account, $this->secure);
				//$cdek_client 	= new \AntistressStore\CdekSDK2\CdekClientV2('TEST');
				$tariff 		= new \AntistressStore\CdekSDK2\Entity\Requests\Tariff();
				$Location 		= new \AntistressStore\CdekSDK2\Entity\Requests\Location();
				$tariff 
					->setToLocation($Location->withCode($this->toPvzId))
					->setFromLocation($Location->withPostalCode($this->fromPostalCode))
					->setType(1) // (1 - "интернет-магазин", 2 - "доставка")
					->setPackageWeight($this->packageWeight)
					->addServices(['PART_DELIV']) //список сервис кодов -  \AntistressStore\CdekSDK2\Constants - SERVICE_CODES
						;
	
				$tariffList = $cdek_client->calculateTariffList($tariff);
				$_aTariffs = false;
				foreach ($tariffList as $tariff) 
				{	
					if (in_array($tariff->getTariffCode(), $this->tariffs)) 
					{
						$_aTariffs[$tariff->getTariffCode()]['tariff_name'] 		= $tariff->getTariffName();
						$_aTariffs[$tariff->getTariffCode()]['tariff_description'] 	= $tariff->getTariffDescription();
						$_aTariffs[$tariff->getTariffCode()]['tariff_code'] 		= $tariff->getTariffCode();
						$_aTariffs[$tariff->getTariffCode()]['delivery_sum'] 		= round($tariff->getDeliverySum());
						$_aTariffs[$tariff->getTariffCode()]['deliveryMinDays'] 	= $tariff->getPeriodMin();
						$_aTariffs[$tariff->getTariffCode()]['deliveryMaxDays'] 	= $tariff->getPeriodMax();
					}
				}
	
				return $_aTariffs;
				
			} catch (\Throwable $th) {

				return False;
			}
		} 

		return False;
	}


}