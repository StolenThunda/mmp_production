$(document).ready(function() {
    $('.service-list').sortable({
        axis: 'y',
        opacity: 0.5,
        update: function() {
            var serviceOrder = [];
            $('.service-list li').each(function() {
                var data = $(this).attr('data-service');
                if (data !== "") serviceOrder.push(data);
            });
            $.post($('.service-list').data('actionUrl'), {
                service_order: serviceOrder.toString(),
                CSRF_TOKEN: EE.CSRF_TOKEN
            });
        }
    });
});