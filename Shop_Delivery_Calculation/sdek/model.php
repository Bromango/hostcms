<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

require_once '/var/www/4dog.su/data/www/4dog.su/modules/shop/delivery/calculation/vendor/autoload.php';

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
	protected $account 		= '0VxF3mE8DDQVHwAm1cXqRbcUC4XnnkhJ';
	protected $secure 		= 'eXJH8qUJAdt39un9tThtkZoNwXSpDojd';

	public $tariffs 		= array(136, 137); //склад-склад / склад-дверь
	public $toPostalCode	= false;
	public $fromPostalCode 	= '357601';
	public $packageWeight 	= 500;


	// список используемых тарифов

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		return $this;
	}

	//Устанавливаем почтовый индекс получателя
	public function setToPostalCode($toPostalCode)
	{
		$this->toPostalCode = $toPostalCode;
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
		$this->packageWeight = $packageWeight;
		return $this;
	}



	// получаем расчет стоимости доставки
	public function calculationOfCostTariffs() 
	{
		if ($this->toPostalCode) 
		{
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
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['tariff_name'] 		= $tariff->getTariffName();
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['tariff_description'] 	= $tariff->getTariffDescription();
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['tariff_code'] 		= $tariff->getTariffCode();
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['delivery_sum'] 		= $tariff->getDeliverySum();
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['deliveryMinDays'] 	= $tariff->getPeriodMin();
	        		$_aTariffs['SdekDelivery'][$tariff->getTariffCode()]['deliveryMaxDays'] 	= $tariff->getPeriodMax();
	        	}
	        }

			return $_aTariffs;

		} else {
			return False;
		}
		
	}


}