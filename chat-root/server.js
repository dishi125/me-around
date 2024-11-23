import { videoCall } from "./video_call/video_call.js";
import { chat } from "./chat/chat.js";
import webSocket from "websocket";
import http from "http";
import {
    createUser,
    loginUser,
    getAllUsers
} from "./controller/user.controller.js";
import {
    getAllChats,
    checkUnreadMessage
} from "./controller/message.controller.js";
import { adminChat,getAllAdminChats,adminchatUnReadMessages } from "./chat/admin.js";
import { groupChat,getAllGroupChats,getAllGroupRepliedChats } from "./chat/group.js";

let WebSocketServer = webSocket.server;
let users = {};
let room = [];
let roomMember = new Map();

let server = http.createServer(function(request, response) {
    // process HTTP request. Since we're writing just WebSockets
    // server we don't have to implement anything.
});
server.listen(4000, function(request, response) {

    setInterval(function() {
        // console.log('timer');
        // console.log(room);
        //console.log(users);

        let uniqueArray = room.filter(function(elem, pos) {
            return room.indexOf(elem) == pos;
        });
        // console.log('timer1');
        for(var i = 0; i < uniqueArray.length; i++){
            var roomID = uniqueArray[i];
            var result = roomID.split('_');
            var user1 = result[0];
            var user2 = result[1];
            //  console.log(user1);
            //  console.log(user2);
            //console.log(users[user1]);
            //console.log(users[user2]);

            if (users.hasOwnProperty(user1)) {
                var userConnection = users[user1];
                if(userConnection && userConnection.length > 0) {
                    //console.log("user 1 connection : " + userConnection.length);
                    userConnection.map((e) => {
                        sendTo(
                            e.connection,
                            {
                                type: "call_status",
                                data: {'success': true, 'message': 'Call is still active'}
                            },
                            "videoCall"
                        );
                    });
                }
            }else{
                console.log('else1');
            }

            if (users.hasOwnProperty(user2)) {
                var user2Connection = users[user2];
                if(user2Connection && user2Connection.length > 0) {
                    console.log("user 2 connection : " + user2Connection.length);
                    user2Connection.map((e) => {
                        sendTo(
                            e.connection,
                            {
                                type: "call_status",
                                data: {'success': true, 'message': 'Call is still active'}
                            },
                            "videoCall"
                        );
                    });
                }
            }
            else{
                console.log('else22');
            }

        }

    }, 10000);

    setInterval(function() {
        // console.log('timer2');
        let uniqueArray = room.filter(function(elem, pos) {
            return room.indexOf(elem) == pos;
        });
        //console.log(users);
        for(var i = 0; i < uniqueArray.length; i++){
            var roomID = uniqueArray[i];
            var result = roomID.split('_');
            var user1 = result[0];
            var user2 = result[1];
            // console.log(users[user1]);
            // console.log(users[user2]);
            var user1Connection = '';
            var user2Connection = '';

            if (users.hasOwnProperty(user2)) {
                var user2Connection = users[user2];
            }

            if (users.hasOwnProperty(user1)) {
                var user1Connection = users[user1];
            }

            if(user1Connection == '' && user2Connection != ''){
                if(user2Connection && user2Connection.length > 0) {
                    console.log("user 1 connection : " + user2Connection.length);
                    user2Connection.map((e1) => {
                        // console.log(e1.connection);
                        sendTo(
                            e1.connection,
                            {
                                type: "leave",
                                data: {'success': true}
                            },
                            "videoCall"
                        );

                    });
                    //delete(users[user2]);
                }

            }

            if(user2Connection == '' && user1Connection != ''){
                if(user1Connection && user1Connection.length > 0) {
                    console.log("user 1 connection : " + user1Connection.length);
                    user1Connection.map((e1) => {
                        // console.log(e1.connection);
                        sendTo(
                            e1.connection,
                            {
                                type: "leave",
                                data: {'success': true}
                            },
                            "videoCall"
                        );

                    });
                    //delete(users[user1]);
                }

            }
        }

    }, 5000);
});

// create the server
let wsServer = new WebSocketServer({
    httpServer: server,
    maxReceivedFrameSize: 100000000,
    maxReceivedMessageSize: 10 * 1024 * 1024,
    autoAcceptConnections: false,
    clientConfig: {
        maxReceivedFrameSize: 100000000,
        maxReceivedMessageSize: 100000000
    }
});

// WebSocket server
wsServer.on("request", function(request) {
    let connection = request.accept(null, request.origin);
    // This is the most important callback for us, we'll handle
    // all messages from users here.
    connection.on("message", function(message) {
        if (message.type === "utf8") {
            // process WebSocket message
            manageIncomingData(message.utf8Data, connection, request);
        }
    });

    connection.on("close", function(connection) {
        // close user connection
        if (request.name && request.device_id) {
            // console.log(request);
            var found_key = -1;
            console.log("Length: " + users[request.name].length);
            if (users.hasOwnProperty(request.name) && users[request.name].length > 0 ) {
                var requestUser = users[request.name];
                var found_key = users[request.name].findIndex(el => (el && el.device_id) == request.device_id);
                if(found_key != -1){
                    //console.log(users);
                    // console.log("found"+ found_key );
                    //delete users[request.name][found_key]
                    requestUser.splice(found_key, 1);
                    users[request.name] = requestUser;

                    /*
                    var users[request.name] = users[request.name].filter(function (el) {
                        console.log('testing el   '+el);
                      return el != null;
                  }); */
                    //  console.log(users);
                    //users[request.name].splice(found_key, 1);
                }
                console.log("found"+ found_key );

            }
            console.log("closing");
            // delete users[request.name];
            console.log("Length: " + users[request.name].length);
        }
    });
});

function sendTo(connection, message, type) {
    var obj = {};
    obj.type = type;
    obj.message = message;
    console.log("obj: "+message.type);
    // console.dir(message, { depth: null });
    connection.send(JSON.stringify(obj));
}

function manageIncomingData(message, connection, request) {
    // console.log("start");
    // console.log(message);
    // console.log(connection);
    // console.log(request);
    // console.log("end");
    let data;
    //accepting only JSON messages
    try {
        data = JSON.parse(message);
        //console.log(data);
    } catch (e) {
        console.log("Invalid JSON");
        console.log("Error stack", e.stack);
        console.log("Error name", e.name);
        console.log("Error message", e.message);
        return;
    }
    switch (data.type) {
        case "addUser":
            console.log("inside adduser");
            var userDetail = JSON.parse(data.message);
            // console.log("userDetail: "+userDetail);
            users.hasOwnProperty(userDetail["from_user_id"]);
            request.name = userDetail["from_user_id"];
            request.device_id = userDetail["device_id"];
            // console.log("Device Id "+ userDetail['device_id'] );
            if (users.hasOwnProperty(userDetail["from_user_id"]) == false ) {
                users[userDetail["from_user_id"]] = [{'device_id':userDetail['device_id'],'is_admin_user':userDetail['is_admin_user'] ?? 0,'connection': connection}];
            }else {
                var is_found = false;
                users[userDetail["from_user_id"]].map((e) => {
                    if(e.device_id == userDetail['device_id']){
                        is_found = true;
                    }
                });
                if(!is_found){
                    users[userDetail["from_user_id"]].push({'device_id':userDetail['device_id'],'is_admin_user':userDetail['is_admin_user'] ?? 0,'connection': connection});
                }
            }
            // console.log("users in add-user: "+users[userDetail["from_user_id"]].connection);
            break;
        case "login":
            loginUser(JSON.parse(data.message), connection);
            break;
        case "register":
            createUser(JSON.parse(data.message), connection);
            break;
        case "getAllUsers":
            getAllUsers(JSON.parse(data.message), connection);
            break;
        case "videoCall":
            videoCall(JSON.parse(data.message), connection);
            break;
        case "chat":
            chat(JSON.parse(data.message), connection);
            break;
        case "getAllChats":
            getAllChats(JSON.parse(data.message), connection);
            break;
        case "adminchat":
            adminChat(JSON.parse(data.message), connection);
            break;
        case "getAllAdminChats":
            getAllAdminChats(JSON.parse(data.message), connection);
            break;
        case "adminchatUnReadMessages":
            adminchatUnReadMessages(JSON.parse(data.message), connection);
            break;
        case "groupchat":
            groupChat(JSON.parse(data.message), connection);
            break;
        case "getAllGroupChats":
            getAllGroupChats(JSON.parse(data.message), connection);
            break;
        case "getAllGroupRepliedChats":
            getAllGroupRepliedChats(JSON.parse(data.message), connection);
            break;
        case "checkUnReadMessages":
            checkUnreadMessage(JSON.parse(data.message), connection);
            break;
    }
}

export { users, sendTo, room, roomMember };
