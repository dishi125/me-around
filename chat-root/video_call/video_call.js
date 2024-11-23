import {sendTo, users , room, roomMember} from "../server.js";
import { connection } from '../models/db.js';
import { Validation } from '../utils/validation.js';
import {admin} from '../firebase.js'

let roomStatus = new Map();
let roomObject = new Map();
let toConnectedUser = [];
let fromConnectedUser = [];

function videoCall(data, socket_connection) {
    switch (data.type) {
        case "new":
        let userId = data.data.username
        console.log(userId);
        sendTo(socket_connection, {
            type: "login",
            data: {'success': true, 'userId': userId}
        },"videoCall");
        break;

        case "createMeeting":
        let roomId = data.data['room_id']
        let fromUsername = data.data['fromUsername']
        let toUsername = data.data['toUsername']
        let fromUserId = data.data['fromUserId']
        let toUserId = data.data['toUserId']
        let type = data.data['type']
        let entityID = data.data['entityID']
        let shopName = data.data['shopName']
        let shopImage = data.data['shopImage']
        let messageId = data.data['messageId']
        let hospitalName = data.data['hospitalName']
        var message = '';

        var hasMember = false;
        for(var entry of roomMember.entries()) {
            var key = entry[0],
            value = entry[1];
            hasMember = value.includes(toUserId)
            if (hasMember) {
                break;
            }
        }

        if (roomId != null && toUserId != null && fromUserId != null) {
            /*
            if(hasMember){

                message = toUsername+" is on another call";
                sendTo(socket_connection, {
                    type: "meeting",
                    data: {'success': false, "message": message}
                },"videoCall");
            }else{ */

                fromConnectedUser[fromUserId] = socket_connection;
                roomMember.set(roomId, [fromUserId, toUserId]);
                roomStatus.set(roomId, false);
                sendTo(socket_connection, {
                    type: "meeting",
                    data: {'success': true}
                }, "videoCall");

                let title = "Incoming call";
                connection.query("SELECT * FROM users_detail WHERE user_id =?", toUserId, (err, res) => {

                    console.log(res);
                    if (err) {
                        console.log(err);
                        const validation = new Validation({
                            message: 'Please contact technical team',
                            status: 500,
                            error: true
                        });
                    } else if (res.length > 0) { 
                        var isUser = 0;
                        var isUserId = 0;
                        if (res.length > 0) {
                            var isUserId = res[0]['user_id']; 
                            isUser = res[0]['user_id'] == toUserId ? 1 : 0;
                        }
                        console.log("Is user "+ isUser );

                        let message = "";
                        if(fromUsername){
                            message = "You are getting call from "+fromUsername;
                        }

                        var token = '';
                        var userName = ''; 
                        if(res[0].user_id == toUserId) {
                            var token = res[0].device_token;
                            userName = toUsername;
                        }
                        console.log('token:' + token);

                        connection.query("SELECT * FROM user_devices where user_id = ? order by id desc",[toUserId], (err, results) => {
                            var tokens = [];
                            if (err) {
                                console.log(err);
                                const validation = new Validation({
                                    message: 'Please contact technical team',
                                    status: 500,
                                    error: true
                                });
                            } else {
                                Object.keys(results).forEach(function(key) {
                                    var device_token = results[key].device_token;
                                    if(device_token.length != 0){
                                        tokens.push(device_token);
                                    }
                                    
                                });
                                if(tokens.length){
                                    var notificationMessage = {
                                        notification : {
                                            'title' : userName,
                                            'body' : message
                                        },
                                        android : {
                                            notification: {
                                                sound: 'notifytune.wav',
                                            },                      
                                        },
                                        apns : {
                                            payload : {
                                                aps : {
                                                    'sound' : 'notifytune.wav'
                                                }
                                            }
                                        },
                                        data : {
                                            'type' : 'video_call',
                                            "click_action" : "FLUTTER_NOTIFICATION_CLICK",
                                            "toUsername": toUsername,
                                            "fromUsername": fromUsername,
                                            "toUserId" : toUserId,
                                            "fromUserId" : fromUserId,
                                            "room_id" : roomId,
                                            "user_type" : type,
                                            "entityID": entityID,
                                            "shopName" : shopName,
                                            "shopImage" : shopImage,
                                            "messageId" : messageId,
                                            "hospitalName" : hospitalName
                                        },
                                        tokens : tokens
                                    }

                                    console.log(notificationMessage);
                                    //  return false;

                                    admin.messaging().sendMulticast(notificationMessage).then((response) => {
                                        // Response is a message ID string.
                                        console.log('Successfully sent notification:', response);

                                        var date_format = new Date();
                                        var current_date = date_format.getFullYear() +'-'+ (date_format.getMonth() + 1)+'-'+ date_format.getDate();
                                        var time_format = date_format.getHours() +':'+ date_format.getMinutes() +':'+date_format.getSeconds();
                                        var sql = "INSERT INTO user_calls (room_id,from_user_id,to_user_id,start_date,end_date,start_time,end_time,media_type) VALUES ('"+roomId+"','"+fromUserId+"', '"+toUserId+"','"+current_date+"',null,'"+time_format+"',null,'video')";

                                        connection.query(sql, function (err, result) {  
                                            if (err) throw err;  
                                            console.log("call record inserted");  
                                        });  

                                    })
                                    .catch((error) => {
                                        console.log('Error sending notification:', error);
                                    });
                                }   
                            }
                        });  
                    }
                });

                var timeOutObject = setTimeout(function(){
                    var message = "";
                    for (var entry of roomStatus.entries()) {
                        var key = entry[0],
                        value = entry[1];

                        if(roomId === key && value === false){
                            message = toUsername+" is not answering the call";
                            console.log('call reject from')
                            /*
                            sendTo(socket_connection, {
                                type: "rejected",
                                data: {'success': true, 'message': message, 'room_id': roomId, 'fromUsername': fromUsername, 'toUsername': toUsername}
                            },"videoCall");
                            */

                            if(socket_connection && socket_connection.length > 0) {
                                console.log("socket_connection rejected: " + socket_connection.length);
                                socket_connection.map((e) => {    
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "rejected",
                                            data: {'success': true, 'message': message, 'room_id': roomId, 'fromUsername': fromUsername, 'toUsername': toUsername}
                                        },
                                        "videoCall"
                                        );
                                });
                            }
                        }
                    }

                    /*
                    console.log(users[toUserId]);
                    socket_connection = users[toUserId];
                    console.log(socket_connection);
                    if(socket_connection != undefined){
                        console.log('call reject123456')
                        sendTo(socket_connection, {
                            type: "rejected",
                            data: {'success': true,'room_id': roomId}
                        },"videoCall");
                    } */

                    if (users.hasOwnProperty(toUserId)) {
                        var socket_connection = users[toUserId];
                        if(socket_connection && socket_connection.length > 0) {
                            console.log("socket_connection : " + socket_connection.length);
                            socket_connection.map((e) => {    
                                sendTo(
                                    e.connection,
                                    {
                                        type: "rejected",
                                        data: {'success': true,'room_id': roomId}
                                    },
                                    "videoCall"
                                    );
                            });
                        }
                    }

                }, 60000);
                roomObject.set(roomId, timeOutObject);
            //}   
        } else {
            sendTo(socket_connection, {
                type: "meeting",
                data: {'success': false}
            },"videoCall");
        }
        break;
        case "connected":
        //let connectedFromUsername = data.data['fromUsername'];
        let connectedRoomId = data.data['room_id'];
       // let connectedToUsername = data.data['toUsername'];
        let connectedToUserid = data.data['toUserId'];
        let connectedFromUserid = data.data['fromUserId'];
        let fromUserConnection = fromConnectedUser[connectedFromUserid];
        toConnectedUser[connectedToUserid] = socket_connection;
        room.push(connectedRoomId);

        console.log("call connected");

        /*
        for(var singleConncetion of users[connectedToUserid]){
            if(socket_connection != singleConncetion){
                sendTo(singleConncetion, {
                    type: "rejected",
                    data: {'success': true, 'message': 'Call received from another device'}
                },"videoCall");
            }
        }*/

        if (fromUserConnection != null) {
            console.log("call connected from");
            var timeOutObject = roomObject.get(connectedRoomId);
            clearTimeout(timeOutObject);
            roomObject.delete(connectedRoomId);
            for (var entry of roomStatus.entries()) {
                var key = entry[0],
                value = entry[1];
                console.log("connected:"+key+'----'+value);
                if(connectedRoomId === key && value === false){
                    roomStatus.set(connectedRoomId, true);
                }
            }

            // To maintain call history
            var lastId = '';
            connection.query('SELECT id  FROM user_calls where room_id = "'+connectedRoomId+'" order by id desc limit 1' , (err,rows) => {
                if(err) throw err;

                rows.forEach( (row) => {
                    lastId = row.id;
                    var sql = 'UPDATE user_calls SET status = 1 WHERE id = "'+lastId+'"';

                    connection.query(sql, function (err, result) {
                        if (err) throw err;
                        console.log(result.affectedRows + " record(s) updated");
                    });
                });
            });

            console.log("room call connected");
            sendTo(fromUserConnection, {
                type: "connected",
                data: data.data
            },"videoCall");
        }
        break;
        case "request":
        let requestedRoomId = data.data['toUsername'];
        let requestedPassword = data.data['password'];
        if (!room.hasOwnProperty(requestedRoomId) || room[requestedRoomId] !== requestedPassword) {
            sendTo(socket_connection, {
                type: "requestDenied",
                data: data.data
            },"videoCall")
        } else if (roomMember[requestedRoomId].length >= 6) {
            sendTo(socket_connection, {
                type: "roomFull",
                data: data.data
            },"videoCall")
        } else {
            let listOfMember = roomMember[requestedRoomId]
            let connectedUsers = JSON.stringify(listOfMember)
            sendTo(socket_connection, {
                type: "requestAccess",
                data: data.data,
                "users": connectedUsers
            },"videoCall")
            listOfMember.push(data.data['fromUsername'])
            roomMember[requestedRoomId] = listOfMember
        }
        break;
        case "candidate":
        let candidateConnection = toConnectedUser[data.data['toUserId']];
        console.log('call candidate');
        if (candidateConnection != null) {
            sendTo(candidateConnection, {
                type: "candidate",
                data: data.data
            },"videoCall");
        }

        candidateConnection = fromConnectedUser[data.data['toUserId']];

        if (candidateConnection != null) {
            sendTo(candidateConnection, {
                type: "candidate",
                data: data.data
            },"videoCall");
        }
        break;
        case "offer":
        //let offerConn = toConnectedUser[data.data['toUsername']];
        let offerConn = toConnectedUser[data.data['toUserId']];
        //console.log(offerConn);

        console.log('offer to call');
        if (offerConn != null) {
            console.log('offer to call if');
            sendTo(offerConn, {
                type: "offer",
                data: data.data,
            },"videoCall");
        }else{
            console.log('offer to else');
        }

        let offerFromConn = fromConnectedUser[data.data['toUserId']];
        //console.log(offerFromConn);
        console.log('offer from call');
        if (offerFromConn != null) {
            console.log('offer from call if');
            sendTo(offerFromConn, {
                type: "offer",
                data: data.data,
            },"videoCall");
        }else{
            console.log('offer from else');
        }
        break;
        case "answer":
        let answerConnection = toConnectedUser[data.data['toUserId']];
        console.log('answer to call');
        if (answerConnection != null) {
            console.log('answer to call if');
            sendTo(answerConnection, {
                type: "answer",
                data: data.data
            },"videoCall");
        }

        answerConnection = fromConnectedUser[data.data['toUserId']];

        if (answerConnection != null) {
            console.log('answer from call if');
            sendTo(answerConnection, {
                type: "answer",
                data: data.data
            },"videoCall");
        }
        break;
        case "reject":
        var room_id = data.data['room_id'];
        var fromUsersname = data.data['fromUsername'];
        var toUsersname = data.data['toUsername'];
        var connectedfromUserId = data.data['fromUserId'];
        var connectedtoUsersId = data.data['toUserId'];
        console.log('call rejected');
        var timeOutObject = roomObject.get(room_id);
        clearTimeout(timeOutObject);
        roomObject.delete(room_id);

        if(fromConnectedUser[connectedfromUserId] !== undefined){
            console.log('call has been declined');
            sendTo(fromConnectedUser[connectedfromUserId], {
                type: "rejected",
                data: {'success': true, 'message': 'The call has been declined', 'data': data.data}
            },"videoCall");
        }
        break;
        case "call_cut":
        var room_id = data.data['room_id'];
        var callFromUserId = data.data['fromUserId'];
        var callToUserId = data.data['toUserId'];
        console.log('call cut');

        var timeOutObject = roomObject.get(room_id);
        clearTimeout(timeOutObject);
        roomObject.delete(room_id);
        
        console.log(users[callFromUserId]);
        console.log(users[callToUserId]);
        if (users.hasOwnProperty(callFromUserId)) {
            console.log("From User call cut");
            var fromConnection = users[callFromUserId];
            if(fromConnection && fromConnection.length > 0) {
                console.log("fromConnection11 : " + fromConnection.length);
                fromConnection.map((e) => {    
                    sendTo(
                        e.connection,
                        {
                            type: "bye",
                            data: data.data
                        },
                        "videoCall"
                        );
                });
            }
        }

        if (users.hasOwnProperty(callToUserId)) {
            console.log("To User call cut");
            var toConnection = users[callToUserId];
            if(toConnection && toConnection.length > 0) {
                console.log("toConnection11 : " + toConnection.length);
                toConnection.map((e) => {    
                    sendTo(
                        e.connection,
                        {
                            type: "call_cut",
                            data: data.data
                        },
                        "videoCall"
                        );
                });
            }
        }

        break;
        case "checkCallStatus":
        var room_id = data.data['room_id'];
        console.log(room_id);
        let isCall = false;

        for (var entry of roomMember.entries()) {
            var key = entry[0];
            if(key === room_id && isCall !== true){
                isCall = true;
            }
        }
        console.log('is call' + isCall);
        if(isCall){
            console.log('is in call');
            sendTo(socket_connection, {
                type: "call_status",
                data: {'success': true, 'message': 'Call is still active'}
            },"videoCall");
        }else{
            console.log('is not in call');
            sendTo(socket_connection, {
                type: "call_status",
                data: {'success': false, 'message': 'Call is not active'}
            },"videoCall");
        }
        break;
        case "callStateStatus":

        var timeOutObject = setTimeout(function(){
            var roomID = data.data['room_id'];
            var connectedUserId = data.data['user_id'];
            var roomMembers = roomMember.get(roomID);
            console.log(roomMembers);

            if(roomMembers){

                /*
                let userConnection = users[roomMembers[connectedUserId]];
                console.log(roomMembers[connectedUserId]);
                console.log(userConnection);
                if(userConnection != undefined){
                    console.log('connected user connection undefined');
                    sendTo(userConnection, {
                        type: "bye",
                        data: data.data
                    },"videoCall");
                } */

                if (users.hasOwnProperty(users[roomMembers[connectedUserId]])) {
                    var userConnection = users[roomMembers[connectedUserId]];
                    if(userConnection && userConnection.length > 0) {
                        console.log("userConnection33 : " + userConnection.length);
                        console.log('connected user connection undefined');
                        userConnection.map((e) => {    
                            sendTo(
                                e.connection,
                                {
                                    type: "bye",
                                    data: data.data
                                },
                                "videoCall"
                                );
                        });
                    }
                }
            } 
        }, 5000);
        break;
        case "getRoomData":
          var user = data.data['name'];
          for (var entry of roomMember.entries()) {
            var key = entry[0], value = entry[1];
            if(value.includes(user)){
              return key;
            }
          }
        break;
        case "bye":
        var connectedFromUserId = data.data['fromUserId'];
        var roomID = data.data['room_id'];
        var roomMembers = roomMember.get(roomID);
        console.log(connectedFromUserId);
        console.log(roomMembers);

        if(roomMembers){
            for (var i = 0; i < roomMembers.length; i++) {
                if(fromConnectedUser[roomMembers[i]] != undefined && roomMembers[i] !== connectedFromUserId){
                    // Leave
                    let leaveConnection = fromConnectedUser[roomMembers[i]];
                    //console.log(leaveConnection);
                    console.log('leave');
                    sendTo(leaveConnection, {
                        type: "leave",
                        data: data.data
                    },"videoCall");
                }

                if(toConnectedUser[roomMembers[i]] != undefined && roomMembers[i] !== connectedFromUserId){
                    // bye
                    console.log('leave 1');
                    let byeConnection = toConnectedUser[roomMembers[i]];
                    //console.log(byeConnection);
                    
                    sendTo(byeConnection, {
                        type: "leave",
                        data: data.data
                    },"videoCall");
                    
                }
                       
                if (toConnectedUser.hasOwnProperty(roomMembers[i])) {
                    console.log('to user removed');
                    delete(toConnectedUser[roomMembers[i]]);
                    //delete(users[roomMembers[i]]);
                }
                if (fromConnectedUser.hasOwnProperty(roomMembers[i])) {
                    console.log('from user removed');
                    delete(fromConnectedUser[roomMembers[i]]);
                    //delete(users[roomMembers[i]]);
                }
            }

            var date_format = new Date();
            var current_date = date_format.getFullYear() +'-'+ (date_format.getMonth() + 1) +'-'+ date_format.getDate();
            var time_format = date_format.getHours() +':'+ date_format.getMinutes() +':'+date_format.getSeconds();
            var callId = '';
            connection.query('SELECT id  FROM user_calls where room_id = "'+roomID+'" order by id desc limit 1' , (err,rows) => {
                if(err) throw err;
                rows.forEach( (row) => {
                    callId = row.id;
                    var sql = 'UPDATE user_calls SET end_date = "'+current_date+'" , end_time = "'+time_format+'" WHERE id = "'+callId+'"';
                    connection.query(sql, function (errq, result) {
                        if (errq) throw errq;
                        console.log(result.affectedRows + " record(s) updated on bye");
                    });
                });
            });


            if (roomMember.has(roomID)) {

                //room.delete(roomID);
                room.splice(room.indexOf(roomID),1);
                roomMember.delete(roomID);
                roomStatus.delete(roomID);
                var timeOutObject = roomObject.get(roomID);
                clearTimeout(timeOutObject);
                roomObject.delete(roomID);
                console.log('room deleted');
            }
        }
        break;
        }
    }

    export {videoCall};