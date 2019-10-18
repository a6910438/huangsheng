$(function () {
    $(".account").focus(function(){
        $('.login_error').hide();
    });
    $(".password").focus(function(){
        $('.login_error').hide();
    });
   $(".input-code").focus(function(){
        $('.code_error').hide();
    });
    $('#login').click(function () { 
        var nick_name=$('.account').val();
        var password=$('.password_input').val();
        var code1=$('.input-code').val();
        if(code1!=code) return $('.code_error').show();
        function getQueryString(name) { 
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
            var r = window.location.search.substr(1).match(reg); 
            if (r != null) return unescape(r[2]); 
            return null; 
        } ;
        var types= getQueryString('types');
        $.ajax({
            url: "../../index.php/index/publics/login",
            type: 'POST',
            data: {nick_name: nick_name,password: password},
            dataType: 'json',
            success: function(res){
                if(res.code=='0'){
                        layer.open({
                            content: 'login successful',
                            skin: 'msg',
                            time: 2
                        });
                        setTimeout(function(){
                            location.href="index.html";
                        }, 500);                   
                    }
                else if(res.code==1&&res.info==1){
                    setTimeout(function(){
                        location.href="thawing_coins.html?type=1&getname="+nick_name;
                    }, 500);
                }
                else if(res.code==1&&res.info==2){
                    setTimeout(function(){
                        location.href="thawing_coins.html?type=2&getname="+nick_name;
                    }, 500);
                }
                else if(res.code==1&&res.info==3){
                    setTimeout(function(){
                        location.href="thawing_coins.html?type=3&getname="+nick_name;
                    }, 500);
                }
                else if(res.code==1&&res.info==4){
                    setTimeout(function(){
                        layer.open({
                            content: 'Please conduct risk assessment',
                            skin: 'msg',
                            time: 2
                        });
                    }, 500);
                    setTimeout(function(){
                        location.href="risk_assessment.html?getname="+nick_name+"&password="+password;
                    }, 500);
                }
                else if(res.code==1&&res.info==5){
                    setTimeout(function(){
                        layer.open({
                            content: 'Please purchase a registration activation currency',
                            skin: 'msg',
                            time: 2
                        });
                    }, 500);
                    setTimeout(function(){
                        location.href="purchase_currency.html?getname="+nick_name;
                    }, 500);
                }
                else if(res.code==1&&res.info==6){
                    setTimeout(function(){
                        layer.open({
                            content: 'Please wait for the review to pass',
                            skin: 'msg',
                            time: 2
                        });
                    }, 500);
                }
                else{
                    layer.open({
                        content: res.msg,
                        skin: 'msg',
                        time: 2
                    });
                    $('.login_error').show();
                    }
            },
            })
    });   
});