$.ajax({
        url: "../../index.php/index/Match/order",
        type: 'POST',
        dataType: 'json',
        async: true,
        crossDomain: true,
        success: function(res) {
            if (res.code == 1 && res.url == "login") {
                layer.open({
                    content: 'You are not logged in',
                    skin: 'msg',
                    time: 2
                })
                setTimeout(function(){
                    window.location.href='introduce.html';
                },500) 
                
            }
        }
        })