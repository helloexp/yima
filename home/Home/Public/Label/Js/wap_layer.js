/*
*wap页面弹层
*open popup
*/

$(function(){
    event.preventDefault();

    //close popup
    $('.cd-popup').on('click', function(event) {
        if ($(event.target).is('.cd-popup-close') || $(event.target).is('.cd-popup')) {
            event.preventDefault();
            var url = $('input[name=url]').val();
            if(url != '0' && typeof(url) != 'undefined'){
                window.location.href = url;
            }
            $(this).removeClass('is-visible');
        }
    });

    //close popup when clicking the esc keyboard button
    $(document).keyup(function(event) {
        if (event.which == '27') {
            var url = $('input[name=url]').val();
            if(url != '0' && typeof(url) != 'undefined'){
                window.location.href = url;
            }
            $('.cd-popup').removeClass('is-visible');
        }
    });
})

