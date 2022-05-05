<?php
// перевести часть конфигурацилнных пунктов в hostcms config
return array (
	'composerAutoloadPath' => __DIR__ . '/vendor/autoload.php',
	'DIR' 	=> __DIR__,
	//Если TRUE, то почтовому индексу получает id доставки из базы данных, если таблица с обновленными городами не установленна, то укажите FALSE
	'fromDatabase' => true,
	// По умолчанию таблица с городами shop_country_location_delivery_cities, бодробнее о ней описанно тут: https://github.com/bromango/hostcms/blob/main/shop_country_location_cities/readme.md
	'dbTableName' => 'shop_country_location_delivery_cities', 
	'cookieLifeTime' => 3600 * 24 * 30, // время хранения данных в coocie = 30 дней
	'HostCmsShopId' => 1,
	'geodata' => array(
		'dadata' => array(
				'token' 	=> 'b44617a9c86ecdd65fbd1b095d381c2c89f6e8d6', // ok 
				'secret'	=> '23894db1c12e6a573395a37a56e9365916efa581'
			)
			/*array(
				'token' 	=> 'dad56fc1760f57229f8af7b9da4955fbcb22b49c',
				'secret'	=> 'c431ad110a12b95084c4487ae7cb8c1a1e0721f9'
			)
			array(
				'token' 	=> 'ce8f01eba3938855b23e390a6e8372664cd19444',
				'secret'	=> '0ede96b50e7609781370e4b27d39044ddd0ff32a'
			)
			array(
				'token' 	=> '1c6364d111c57ab25254b1d646be24fa9ca0020c',
				'secret'	=> '1d80c0c996bbc2b8ea7a79701edf3e0df5eb408c'
			)
			array(
				'token' 	=> '70ba332731ad5ab3bb5f419118f947c4f00f5421',
				'secret'	=> '82662191193cfe4d45d3b988ba5fc2cc89e2408c'
			)
			array(
				'token' 	=> '19198f1bde3c01850d200c27109f0fe36bafd746',
				'secret'	=> 'ef126c8044f83d31111b14ee0447678871c62314'
			)
			array(
				'token' 	=> '3bddc0f06fd73e86a73f3481e90c34a6c0cb6c00',
				'secret'	=> '67150ced897c7f4c92b61333afe4ee90e5404204'
			)
			array(
				'token' 	=> '5209f8ee89dda4fa78dbed79945638587dd5ec32',
				'secret'	=> '8983c9258cbbd5741abbc54244c975f00b71e870'
			)*/
	),
	'patternXMLdeliveryItem' => 'СписокАктуальныхДоставок(КартаТовара)',
	'patternXMLdeliverycart' => 'СписокАктуальныхДоставок(ОформлениеЗаказа)',
	'deliveries' => array(
		'sdek' => array(
			'active'	=> true,
			'account' 	=> '0VxF3mE8DDQVHwAm1cXqRbcUC4XnnkhJ', 
			'secure' 	=> 'eXJH8qUJAdt39un9tThtkZoNwXSpDojd',
			'tariffs' 	=> array(136, 137),				// Идентификаторы требуемых тарифов
			'fromPostalCode' => '357601',				// Почтовый индекс отправителя
			'packageWeight' => 1001,					// Вес отправления в граммах по умолчанию
			'fromSdekId'	=> 0, 						// Идентификатор города отправителя по БД СДЭК
			'id'			=> array(9, 10), 			// Идертификтор в системе HostCms
			136 			=> 9,						// соответсвие тарифа сдэка и доставки hostcms
			137				=> 10,
			'courier'		=> 10,
			'pickup'		=> 9
		),
		'russianpost' => array(
			'active'	=> true,
			'fromPostalCode' => '357601',
			'authentication' => array(
				'auth' => array(
					'otpravka' => array(
						'token' => '6_JYGOL47toM9rfeTBebExNLCxD7r4D6', 
						'key' 	=> 'U2hvcEBtdGJpa2VzLnJ1OnJhejExZ2xheg=='
					),
					'tracking' => array(
						'login' 	=> 'Shop@mtbikes.ru', 
						'password' 	=> 'raz11glaz'
					)
				)
			),
			'id'			=> array(8, 1),
			'packageWeight' => 500,
			'prepayment' 	=> 1,
			'cash_on_delivery' => 8,
			'extra_charge' 	=> 8 // наценка для наложного платежа (цена доставки  * 8%) 
		),
		'boxberry' => array(
			'active'	=> true,
			'token'		=> '0c0f20a0c390def0ca5da232f025a4d4',
			'packageWeight' => 500,
			'id'	=> array(12, 0),
			'amount' => 1000, 	//миимальная стоимость заказа по умолчанию 
			'extra_charge' 	=> 2, // Наценка на доставку в процентах
			'courier'		=> 0,
			'pickup'		=> 12
		),
		'ozon' => array(
			'active'	=> false,
			'id'	=> array(15)
		)
	)
);
