// 假设服务端ip为127.0.0.1
ws = new WebSocket("ws://127.0.0.1:2346");
ws.onopen = function() {
    ws.send('tom');
};
ws.onmessage = function(e) {
    console.log("收到服务端的消息：" + e.data);
};