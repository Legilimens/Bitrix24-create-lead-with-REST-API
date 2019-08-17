<?php
header('Access-Control-Allow-Origin: *'); 

/*
    UF_CRM_1563785863, как и UF_CRM_1562309647 - пользовательские поля, созданные в интерфейсе Битрикс24
    необходимы для передачи выбранного тарифа и отмеченного чекбокса о необходимости спецсчета

    Подробнее обо методе работы и формирования лида через API можно почитать тут:
    https://gettotop.ru/crm/bitrix24-lidy-s-sajta-avtomaticheskoe-sozdanie-lidov/
    https://blog.budagov.ru/bitrix24-sozdanie-lida-cherez-api/
    https://gettotop.ru/crm/polzovatelskie-polya-v-bitrix24-peredacha-znachenij-v-lidax/
*/

//На всякий случай формируем логи заполнения формы
$log_text = "Сайт: ". $_SERVER['SERVER_NAME']
            . "\r\nКонтактное лицо: " . $_POST['NAME']
            . "\r\nТелефон: " . $_POST['PHONE_MOBILE']
            . "\r\Тариф: " . isset($_POST['UF_CRM_1563785863']) ? $_POST['UF_CRM_1563785863'] : "-"
            . "\r\nКомментарий: " . $_POST['COMMENTS']
            . "\r\nМне нужен спецсчет для госторгов: " . isset($_POST['UF_CRM_1562309647']) ? ( $_POST['UF_CRM_1562309647']  == "Y" ? "Да" : "Нет" ) : "-" 
            . "\r\n";

//Записываем логи
$fp = fopen("./logs/".date("m-Y").".log","a+t");
$to_write = date("Y-m-d")."\t".date("H:i:s")."\n".$log_text."\r\n";          
if (!fwrite($fp,$to_write)) {
	fclose($fp);
	die("Ошибка при отправке. Невозможно записать файл журнала. Обратитесь в поддержку сайта.");
}

// формируем URL в переменной $queryUrl для обращения через вебхук
//например: https://bitrix.bitrix24.ru/rest/90/td12few5kr5gnds22/crm.lead.add.json
$queryUrl = 'https://АДРЕС_САЙТА.ru/rest/ИДЕНТИФИКАТОР_ПОЛЬЗОВАТЕЛЯ_СОЗДАВШЕГО_ВЕБХУК/ИДЕНТИФИКАТОР_ВЕБХУКА/crm.lead.add.json';

// формируем параметры для создания лида в переменной $queryData
$queryData = http_build_query(array(
    'fields' => array(
        //статус нового лида
        "STATUS_ID" => "NEW",
        //доступен всем
        "OPENED" => "Y",
        //Наименование лида 
        'TITLE' => 'Заполнение формы с сайта',
        //идентификатор ответственного лица
        'ASSIGNED_BY_ID' => 'ИДЕНТИФИКАТОР_ОТВЕТСТВЕННОГО',
        //имя клиента из формы
        'NAME' => $_POST['NAME'],
        //мобильный телефон клиента из формы
        'PHONE' => isset($_POST['PHONE_MOBILE']) ? array(array('VALUE' => $_POST['PHONE_MOBILE'], 'VALUE_TYPE' => 'MOBILE')) : array(),
        //комментарий клиента из формы
        'COMMENTS' => $_POST['COMMENTS'],
        //Тариф формы, из которой клиент сделал заявку
        'UF_CRM_1563785863' => isset($_POST['UF_CRM_1563785863']) ? $_POST['UF_CRM_1563785863'] : array(),
        //Отмеченный флажок "Мне нужен спецтариф для госторгов"
        'UF_CRM_1562309647' => isset($_POST['UF_CRM_1562309647']) ? $_POST['UF_CRM_1562309647'] : array()
    ),
    'params' => array("REGISTER_SONET_EVENT" => "Y")
));

// обращаемся к Битрикс24 при помощи функции curl_exec
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_POST => 1,
    CURLOPT_HEADER => 0,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $queryUrl,
    CURLOPT_POSTFIELDS => $queryData,
));
$result = curl_exec($curl);
curl_close($curl);
$result = json_decode($result, 1);

//формируем json-ответ
if (array_key_exists('error', $result)) {
    $error = array(
        'success' => false,
        'result' => "Ошибка: ".$result['error_description']
    );
    echo json_encode($error);
} else {
    $success = array(
        'success' => true,
        'result' => "Спасибо!<br/>Ваша заявка принята"
    );
    echo json_encode($success);
}

?>