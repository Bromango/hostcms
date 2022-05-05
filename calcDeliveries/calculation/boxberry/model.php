<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

//require_once Shop_Delivery_Calculation_Model::getConfig('composerAutoloadPath');

/**
 * Shop_Delivery_Calculation_Boxberry_Model
 *
 * @package HostCMS
 * @subpackage Shop
 * @version 6.x
 * @author Baisungurov Roman
 * @copyright
 */

class Shop_Delivery_Calculation_Boxberry_Model extends Core_Entity
{

    /**
	 * Хранит авторизационные данные
	 */
	protected $token 		= false;
	public $toPvz 			= false;
	public $packageWeight 	= false;
	public $amount			= false;


	// список используемых тарифов

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		$_Deliveries = Shop_Delivery_Calculation_Model::getConfig('deliveries');

		$this->token 			= $_Deliveries['boxberry']['token'];
		$this->packageWeight 	= $_Deliveries['boxberry']['packageWeight'];
		$this->amount 			= $_Deliveries['boxberry']['amount'];

		return $this;
	}


    //Устанавливаем идентификатор пункта самовывоза боксбери
	public function setToPvzId($toPvz)
	{
		$this->toPvz = $toPvz;
		return $this;
	}
	
	//устанавливаем обьем посылки 
	public function setPackageWeight($packageWeight)
	{
		if ($packageWeight > (int)$this->packageWeight) $this->packageWeight = $packageWeight;

		return $this;
	}

	//устанавливаем стоимость посылки 
	public function setAmount($amount)
	{
		if ($amount > (int)$this->amount) $this->amount = $amount;

		return $this;
	}

    // получаем расчет стоимости доставки
	public function calculationOfCostTariffs() 
	{
		if (empty($this->toPvz))  return False;
		
		try {
			
			$url = 'https://api.boxberry.ru/json.php?token='.$this->token.'&method=DeliveryCosts&weight='.$this->packageWeight.'&target='.$this->toPvz.'&ordersum='.$this->amount;

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
				"Accept: application/json",
			);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			//for debug only!
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

			$data = json_decode(curl_exec($curl),true);
			curl_close($curl);
					
			if (!count($data)) return False;
				
			$_Delivery = array();
			$_Delivery['tariff_name'] 		= 'DeliveryCosts';
			$_Delivery['delivery_sum'] 		= round($data['price']);
			$_Delivery['deliveryMinDays'] 	= $data['delivery_period'];
			$_Delivery['deliveryMaxDays'] 	= $data['delivery_period'];

			return $_Delivery;
			
		} catch (\Throwable $th) {
			
			return False;
		}

		

		
	}
    
}