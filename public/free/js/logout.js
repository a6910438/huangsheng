$(function () {    
    $('.layout').off().on('click',function(){
        $.ajax({
                    url: "../../index.php/index/Member/logout",
                    type: 'POST',
                    dataType: 'json',
                    success: function(res){
                        if(res.code=='0'){
                                setTimeout(function(){
                                    layer.open({
                                        content: res.message,
                                        skin: 'msg',
                                        time: 2
                                    });
                                    window.location.href="login.html";
                                }, 1000);
                            }
                        else{
                            
                            layer.open({
                                content: res.message,
                                skin: 'msg',
                                time: 2
                            });
                            }
                    },
            })
    })
});