let loginFlag;
let userId = '';
$.ajax({
    url: j_url + "/index/member/is_login",
    type: "GET",
    data: {},
    dataType: "json",
    async: true,
    crossDomain: true,
    success: function(res) {
        
        if (res.code == 1 && res.url == "login") {
            loginFlag = false;
        }
        if (res.code == 0) {
            loginFlag = true;
            userId = res.info;
        }
    }
})
