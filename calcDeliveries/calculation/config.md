# Описание файла конфигурации

Статичный доступ к файлу:
```php
    // Возвращает весь массив конфига
    Shop_Delivery_Calculation_Model::getConfig();

    // Возвращает по названию конфигурационного блона или настроки
    Shop_Delivery_Calculation_Model::getConfig($name);
```
Доступ к файлу через Core_Entity:
```php
    $_Object = Core_Entity::factory('Shop_Delivery_Calculation', 0);

    // Возвращает весь массив конфига
    $_Config = $_Object->getConfig();

    // Возвращает по названию конфигурационного блона или настроки
    $_Config = $_Object->getConfig($name);
```
```composerAutoloadPath```: Путь к autoload.php composer  
```fromDatabase```: Разрешение получать данные из [обновленной базы данных](https://github.com/bromango/hostcms/tree/main/shop_country_location_cities) (если она есть в системе).  
```dbTableName```: Название таблицы городов из  [обновленной базы данных](https://github.com/bromango/hostcms/tree/main/shop_country_location_cities). Например: shop_country_location_delivery_cities  
```cookieLifeTime```: Время хранения данных в COOKIE (3 дней = 3600 * 24 * 30)  
```geodata```: Список используемых аккаунтов, для доступа к сервису [dadata.ru](https://dadata.ru/api/)  
```patternXMLdeliveryItem```: Название XSL шаблона используемого при отображении списка расчитанных доставок на странице товара.  
```deliveries```: Настройки служб доставки: СДЭК / Почта России / Боксбери. Активность, акторизационные данные, наценка, идентификаторы соответствия доствкам в HostCms и Другие настройки. 
