'use strict';

$( document ).ready( function() {
    
    $('#bx_create_lead').click(function() { 
        
        //находим форму из которой пришел эвент
        let $formEl = $(this).closest("form");
        //поле имя
        let $clientName = $formEl.find('.client-name').val();
        //поле номера телефона
        let $clientPhone = $formEl.find('.client-phone').val();
        //поле тарифа
        let $clientTariff = $formEl.find('.client-tariff').val();
        //поле идентификатора метрики
        let $ymTarget = $formEl.find('.ym-target').val();
        //поле комментария
        let $leadComment = $formEl.find('.client-comment').val();
        //нужен ли спец счет для госторгов
        let $specialInvoiceCheck = $( $formEl.find('.special-invoice') ).prop('checked');
        let $specialInvoice = "";
        if($specialInvoiceCheck) 
            $specialInvoice = $formEl.find('.special-invoice').val();
        else $specialInvoice = "N";
        //согласие на обработку персональных данных
        let $agreement = $formEl.find('.bx-lead-agreement');

        //проверяем имя, номер телефона и согласие на обработку персональных данных
        let errorList = [];
        if ($clientName == "")
            errorList.push("Имя не может быть пустым!");
        if ($clientPhone == "")
            errorList.push("Некорректный номер телефона!");
        if ($agreement.length && !$agreement.prop("checked")) 
            errorList.push('Для продолжения необходимо согласиться с обработкой персональных данных!');   

        //если имеются ошибки - оповещаем, иначе пытаемся создать лида
        if(errorList.length > 0) {
            openModal("Допущены ошибки", errorList.join('<br />'));
        } else {
            $.post(
                window.location + "BXSend.php",
                {
                    NAME: $clientName,
                    PHONE_MOBILE: $clientPhone,
                    UF_CRM_1563785863: $clientTariff,
                    COMMENTS: $leadComment, 
                    UF_CRM_1562309647: $specialInvoice
                })
                .done(function(successData) {
                    try {
                        let $data = $.parseJSON(successData);
                        if($data.success == true){
                            $formEl.trigger("reset");
                            openModal("Спасибо!", $data.result);
                            //раскомментировать и заменить __COUNTER_ID__ при необходимости создать достижение яндекс-цели, при успешном создании лида
                            //yaCounter__COUNTER_ID__.reachGoal($ymTarget);
                        } else 
                            openModal("Допущены ошибки", $data.result);
                    } 
                    catch (err) {
                        openModal("Что-то пошло не так...", "При отправке данных произошла ошибка: "+err);
                    }
                })
                .fail(function(errorData) {
                    openModal("Что-то пошло не так...", "Ошибка при отправке формы. Пожалуйста, обратитесь в техподдержку сайта.<br />Ошибка: " + errorData);
                });
        }
        
    });
    
});

const openModal = (title, body) => {
    $("#notification .modal-title").html(title);
    $("#notification .modal-body").html(body);
    $('#notification').modal()
}