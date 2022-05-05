# Описание методов Shop_Delivery_Calculation_Model

Доступ к модулю через Core_Entity:
```php
   $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
```
Доступ к статичным методам:
```php
    Shop_Delivery_Calculation_Model::getConfig($name);
    Shop_Delivery_Calculation_Model::addToCookie($value, $name);
    Shop_Delivery_Calculation_Model::getCookie($name);
    Shop_Delivery_Calculation_Model::addToSession($value, $name);
    Shop_Delivery_Calculation_Model::clearSession($name);
    Shop_Delivery_Calculation_Model::clearCookie($name):
```
_____
## Основные методы
#### $_Object->getUserGeodata()
При первом обращении получает (и отдает) данные геолокации пользователя по IP адресу и пишет в $_SESSION и $_COOKIE.
При последующих обращениях отдает геоданные пользователя из $_SESSION или $_COOKIE.
```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
    $Location = $_Object->getUserGeodata();

    /*  Пример результата Ростова-на-Дону
    [location] => Array(
        [shop_country_location_id] => 49
        [id] => 1677
        [postal_code] => 344000
        [sdek_id] => 438
        [boxberry_id] => 44
        [dpd_id] => 49270397
        [region_kladr_id] => 6100000100000
        [region_fias_id] => c1cfe4b9-f7c2-423c-abfa-6ed1c05a15c5
    )
    */
```
#### $_Object->updateUserGeodata($locationCityId)
Обновляет данные геолокации польльзователя по указанному ID города сестемы HostCms.
Принимает id города из справочника стран и городов системы HostCms. Пишет город и его данные в $_SESSION и $_COOKIE.
Операется на расширенную [базу данныех регионов и городов](https://github.com/bromango/hostcms/tree/main/shop_country_location_cities)

```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
    $Location = $_Object->updateUserGeodata($locationCityId);
```
### Методы доставки
#### $_Object->getDeliveries($weight, $amount, $AreaId, $CityId);
Отдает массив с доступными для выбранного региона / города доставками с обновленной стоимостью доставки. Берет список доступных в системе доставок, для каждой (если есть возможность) обновляет цену на лету. 
```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
    $aDeliveryList = $_Object->getDeliveries($weight, $amount, $AreaId, $CityId);

     /*  Пример результата
    Array(
        [10] => Array(
            [id] => 10
            [name] => Курьер (СДЭК)
            [description] => Доставка курьером компании СДЭК в понедельник - пятницу с 10:00 до 18:00 и в субботу с 10:00 до 16:00
            [price] => 410
            [shop_delivery_condition_id] => 1573
            [deliveryMinDays] => 3
            [deliveryMaxDays] => 4
            [calculation] => 1
        )
        [9] => 
    */
```
Использует методы:
```php
    $_Object->getUserGeodata();                     // Получает пункт назначения
    $_Object->getDeliveryIdFromDB($_postal_code);   // Получает идентификаторы служб доставок

    // Расчитывают стоимость в пункт назначения онлайн
    $_Object->getPriceSdekDeliveryByPostcode($_postal_code, $weight);
    $_Object->getPriceRussianpostByPostcode($_postal_code, $weight);
    $_Object->getPriceBoxberryByPvzId($_boxberry_id, $weight, $amount);
```
#### $_Object->getXMLDeliveries($_Array); 
Отдает XML объект (Core_Xml_Entity) сформированный на основе переданного массива. 
```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
    $aDeliveryList = $_Object->getDeliveries($weight, $amount, $AreaId, $CityId);
    $oXMLDeliveries = $_Object->getXMLDeliveries($aDeliveryList);
```
#### $_Object->getXMLListOfCities();
Формирует и отдает готовый XML список всех регионов и городов с отмеченным городом пользователя.
```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);
    $oXMLCitiesList = $_Object->getXMLListOfCities();

    /*  Пример XML
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
```