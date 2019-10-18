$(document).ready(function () {
    $('.panel ul li').hover(function () {
           $(this).css("background-color","#f5f5f5");
        }, function () {
            // out
            $(this).css("background-color","#fff");
        }
    );   
    $('.left_panel').on('click', function(){
        if($('.panel').hasClass('hidecontent')){
            $('.panel').removeClass('hidecontent');
            var pageheight = $('#page').height();
            var docHeight = $(document).height()-pageheight; //获取窗口高度  
            $('.panel').css('height',docHeight);
            $('.panel').css('top',pageheight);
            $('body').append('<div id="overlay"></div>');                
            $('#overlay')
                .height(docHeight)
                .css({
                'opacity': .5, //透明度
                'position': 'absolute',
                'top': pageheight,
                'left': 0,
                'background-color': 'black',
                'width': '100%',
                'z-index': 88//保证这个悬浮层位于其它内容之上
            });
        }else{
            $('.panel').addClass('hidecontent');              
            $('#overlay').remove();
        }
    });
    $(".open_eye").on('click',function(){
        var t = $('.password_input').attr("type");
        if(t=="password"){
            $(".password_input").attr("type", "text");
            $(".open_eye").attr("src", "../images/open-eye.png");
        }else{
            $(".password_input").attr("type", "password");
            $(".open_eye").attr("src", "../images/close-eyes.png");
        }
    });
    var pageheight = $('.header').height();
    var docHeight = $(document.body).height()-pageheight;
    $('#index_content').css('height',docHeight);
})
    