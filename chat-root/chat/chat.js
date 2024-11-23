import { sendTo, users } from "../server.js";
import { Message } from "../models/message.model.js";
import { AdminMessage } from "../models/admin-message.model.js";
import { User } from "../models/user.model.js";
import path from "path";
import fs from "fs";
import moment from "moment";
import 'moment-timezone';
import mime from "mime";
import {connection as dbConnection} from "../models/db.js";
import {Validation} from "../utils/validation.js";
import {GroupMessage} from "../models/group-message.model.js";

// const helper = require('../utils/helper');
// const path = require('path');
// const fs = require('fs');

let room = [];
let roomMember = [];

function chat(data, connection) {
    switch (data.type) {
        case "initiateChat":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            if (
                !room.includes(combinedUsername) &&
                !room.includes(combinedUsername2)
            ) {
                room.push(combinedUsername);
                roomMember[combinedUsername] = [];
            }
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
                if (!roomMates.includes(myUsername)) {
                    roomMates.push(myUsername);
                }
                // roomMember[combinedUsername] = roomMates
            } else {
                roomMates = roomMember[combinedUsername2];
                roomMates.push(myUsername);
                roomMember[combinedUsername2] = roomMates;
            }
            console.log("room:",room);
            console.log("roomMates:",roomMates);

            if (data.data.language!=undefined) {
                dbConnection.query("SELECT * FROM `chat_languages` WHERE `user_id` = ? and `type` = 'chat'", [data.data.from_user_id], (err, res) => {
                    if (err) console.log(err);
                    console.log("res:" + res);
                    if (res.length > 0) {
                        dbConnection.query("UPDATE chat_languages SET language = ? WHERE user_id = ? and `type` = 'chat'", [data.data.language, data.data.from_user_id], (err2, res2) => {
                            if (err2) {
                                console.log("error: ", err2);
                            }
                        })
                    } else {
                        dbConnection.query(
                            "INSERT INTO chat_languages (user_id,type,language) VALUES (?,?,?)", [data.data.from_user_id, "chat", data.data.language],
                            (err3, res3) => {
                                if (err3) {
                                    console.log("error: ", err3);
                                }
                            }
                        );
                    }
                });
            }
            break;

        case "sendMessage":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            console.log('sendMessage');
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
            } else {
                roomMates = roomMember[combinedUsername2];
            }
            var user_chat = data.data.user_chat;

            if (data.data["type"] == "file") {
                var matches = data.data.message.match(
                        /^data:([A-Za-z-+\/]+);base64,(.+)$/
                    ),
                    response = {};

                if (matches.length !== 3) {
                    console.log("Invalid String");
                }
                response.type = matches[1];
                response.data = new Buffer(matches[2], "base64");
                let decodedImg = response;
                let imageBuffer = decodedImg.data;
                let type = decodedImg.type;
                let extension = mime.getExtension(type);
                let fileName = moment() + "." + extension;
                let dir =
                    moment().format("D-M-Y") + "/" + data.data.from_user_id;
                var dir1 = dir.replace(/\/$/, "").split("/");
                for (var i = 1; i <= dir1.length; i++) {
                    var segment =
                        path.basename("uploads") +
                        "/" +
                        dir1.slice(0, i).join("/");
                    !fs.existsSync(segment) ? fs.mkdirSync(segment) : null;
                }
                let filepath = dir + "/" + fileName;
                try {
                    fs.writeFileSync(
                        path.basename("uploads") + "/" + filepath,
                        imageBuffer,
                        "utf8"
                    );
                    console.log("success");
                } catch (e) {
                    console.log(e);
                }

                var msg = new Message({
                    from_user_id: data.data["from_user_id"],
                    to_user_id: data.data["to_user_id"],
                    entity_type_id: data.data["entity_type_id"],
                    entity_id: data.data["entity_id"],
                    is_guest: data.data["is_guest"] ?? 0,
                    is_post_image: data.data.hasOwnProperty("is_post_image")
                        ? data.data["is_post_image"]
                        : 0,
                    message: path.basename("uploads") + "/" + filepath,
                    type: data.data["type"],
                    status: 0,
                    is_default_message: 0,
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                });

                var notificationMessage =
                    path.basename("uploads") + "/" + filepath;
            } else {
                var msg = new Message({
                    from_user_id: data.data["from_user_id"],
                    to_user_id: data.data["to_user_id"],
                    entity_type_id: data.data["entity_type_id"],
                    entity_id: data.data["entity_id"],
                    message: data.data["message"],
                    is_guest: data.data["is_guest"] ?? 0,
                    type: data.data["type"],
                    status: 0,
                    is_default_message: 0,
                    is_post_image: data.data.hasOwnProperty("is_post_image")
                        ? data.data["is_post_image"]
                        : 0,
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                });

                var notificationMessage = data.data["message"];
            }

            Message.addMessage(msg, function(chat) {
                // console.log("Message output id" +chat['id']);
                // console.log("data.data['timezone']: "+data.data['timezone']);
                const inputFormat = 'x';
                const inputTimezone = 'UTC';
                const outputFormat = 'x';
                const outputTimezone = (data.data['timezone']!=undefined) ? data.data['timezone'] : "Asia/Seoul"; // Target timezone
                if (chat['time']!=0){
                    const inputTime = chat["time"]; // Your input string time
                    const convertedTime = moment.tz(inputTime, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                    chat["time"] = parseInt(convertedTime);
                }
                const converted_created_at = moment.tz(chat['created_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                chat["created_at"] = parseInt(converted_created_at);
                const converted_updated_at = moment.tz(chat['updated_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                chat["updated_at"] = parseInt(converted_updated_at);
                if (chat['parent_message_time']!=undefined){
                    const converted_parent_message_time = moment.tz(chat['parent_message_time'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                    chat["parent_message_time"] = parseInt(converted_parent_message_time);
                }
                // console.dir(chat, { depth: null });

                // console.log("roomMates.length: "+roomMates.length);
                if(roomMates && roomMates.length > 0){
                    for (let i = 0; i < roomMates.length; i++) {
                        console.log("Room Length 1 " +roomMates[i]);
                        console.log("User Name " +myUsername);
                        var to_con_user = (data.data.user_chat==true) ? data.data.to_user_id : roomMates[i];
                        var from_con_user = (data.data.user_chat==true) ? data.data.from_user_id : roomMates[i];
                        if (roomMates[i] != myUsername) {
                            if (users.hasOwnProperty(to_con_user)) {
                                var toConnection = users[to_con_user];
                                if(toConnection && toConnection.length > 0) {
                                    // console.log("toConnection : " + toConnection.length);
                                    toConnection.map((e) => {
                                        sendTo(
                                            e.connection,
                                            {
                                                type: "receiveMessage",
                                                data: chat
                                            },
                                            "chat"
                                        );
                                    });
                                }
                            }
                        } else {
                            var fromConnection = users[from_con_user];
                            if(fromConnection && fromConnection.length > 0) {
                                // console.log("fromConnection : " + fromConnection.length);
                                fromConnection.map((e) => {
                                    // console.log("from user "+ roomMates[i]);
                                    // console.log(e.device_id);
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "update",
                                            data: chat
                                        },
                                        "chat"
                                    );
                                });
                            }
                        }
                    }
                }
                // console.log("after message");
		// console.log(chat['id']);
                var entity_id = data.data["entity_type_id"] == 1 ? data.data["entity_id"] : data.data["hospital_id"];
                if (chat['type'] == "shop"){
                    notificationMessage = "Shop shared";
                }
                else if (chat['type'] == "shop_post"){
                    notificationMessage = "Shop post shared";
                }

                User.getUsers(
                    data.data["to_user_id"],
                    data.data["from_user_id"],
                    data.data["entity_type_id"],
                    entity_id,
                    data.data["main_name"],
                    data.data["sub_name"],
                    notificationMessage,
                    chat['id'],
                    data.data["username"],
                    data.data["is_login"],
                    data.data["is_guest"],
                    data.data['message'],
                    data.data['type'],
                    data.data['hospital_id'],
                    data.data['user_id'],
                    data.data['message_id'],
                    data.data['timezone']
                );
            });

            break;

        case "likeMessage":
            var message_id = data.data.message_id;
            var is_liked = data.data.is_liked;

            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
            } else {
                roomMates = roomMember[combinedUsername2];
            }

            dbConnection.query("UPDATE messages SET is_liked = ? WHERE id = ?", [is_liked,message_id], (err, res) => {
                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: "Please contact technical team",
                        status: 500,
                        error: true
                    });
                    sendTo(
                        connection,
                        validation.convertObjectToJson(),
                        "likeMessage"
                    );
                }

                if(roomMates && roomMates.length > 0){
                    for (let i = 0; i < roomMates.length; i++) {
                        var to_con_user = (data.data.user_chat==true) ? data.data.to_user_id : roomMates[i];
                        var from_con_user = (data.data.user_chat==true) ? data.data.from_user_id : roomMates[i];
                        if (roomMates[i] != myUsername) {
                            if (users.hasOwnProperty(to_con_user)) {
                                var toConnection = users[to_con_user];
                                if(toConnection && toConnection.length > 0) {
                                    toConnection.map((e) => {
                                        sendTo(
                                            e.connection,
                                            {
                                                type: "likeMessage",
                                                is_liked: is_liked,
                                                message_id: message_id
                                            },
                                            "likeMessage"
                                        );
                                    });
                                }
                            }
                        } else {
                            var fromConnection = users[from_con_user];
                            if(fromConnection && fromConnection.length > 0) {
                                fromConnection.map((e) => {
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "likeMessage",
                                            is_liked: is_liked,
                                            message_id: message_id
                                        },
                                        "likeMessage"
                                    );
                                });
                            }
                        }
                    }
                }
            });
            break;

        case "removeMessage":
            var message_id = data.data.message_id;
            var from_user_id = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;

            Message.removeMessage(message_id, async function (data) {
                // console.log("data['affectedRows']: "+data['affectedRows']);
                if (data['affectedRows'] > 0) {
                    var fromConnection = users[from_user_id];
                    if (fromConnection && fromConnection.length > 0) {
                        fromConnection.map((e) => {
                            sendTo(
                                e.connection,
                                {
                                    type: "removeMessage",
                                    data: {
                                        message_id: message_id
                                    }
                                },
                                "chat"
                            );
                        });
                    }

                    var toConnection = users[to_user_id];
                    if (toConnection && toConnection.length > 0) {
                        toConnection.map((e) => {
                            sendTo(
                                e.connection,
                                {
                                    type: "removeMessage",
                                    data: {
                                        message_id: message_id
                                    }
                                },
                                "chat"
                            );
                        });
                    }
                }
            });
            break;

        /*case "sendAdminMessage":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            console.log('in sendAdminMessage');
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
            } else {
                roomMates = roomMember[combinedUsername2];
            }

           /!* console.log("hello 1");
            const AdminUsers = await AdminMessage.getAdminUsers();
            console.log("getAdminUsers:",AdminUsers);*!/
            if (data.data["type"] == "file") {
                var matches = data.data.message.match(
                        /^data:([A-Za-z-+\/]+);base64,(.+)$/
                    ),
                    response = {};

                if (matches.length !== 3) {
                    console.log("Invalid String");
                }
                response.type = matches[1];
                response.data = new Buffer(matches[2], "base64");
                let decodedImg = response;
                let imageBuffer = decodedImg.data;
                let type = decodedImg.type;
                let extension = mime.getExtension(type);
                let fileName = moment() + "." + extension;
                let dir =
                    moment().format("D-M-Y") + "/" + data.data.from_user_id;
                var dir1 = dir.replace(/\/$/, "").split("/");
                for (var i = 1; i <= dir1.length; i++) {
                    var segment =
                        path.basename("uploads") +
                        "/" +
                        dir1.slice(0, i).join("/");
                    !fs.existsSync(segment) ? fs.mkdirSync(segment) : null;
                }
                let filepath = dir + "/" + fileName;
                try {
                    fs.writeFileSync(
                        path.basename("uploads") + "/" + filepath,
                        imageBuffer,
                        "utf8"
                    );
                    console.log("success");
                } catch (e) {
                    console.log(e);
                }

                var msg = new AdminMessage({
                    from_user_id: data.data["from_user_id"],
                    entity_type_id: data.data["entity_type_id"],
                    entity_id: data.data["entity_id"],
                    is_guest: data.data["is_guest"] ?? 0,
                    is_post_image: data.data.hasOwnProperty("is_post_image")
                        ? data.data["is_post_image"]
                        : 0,
                    message: path.basename("uploads") + "/" + filepath,
                    type: "file",
                    status: 0,
                    is_default_message: 0,
                    is_admin_message: 1
                });

                var notificationMessage = path.basename("uploads") + "/" + filepath;
            } else {
                var msg = new AdminMessage({
                    from_user_id: data.data["from_user_id"],
                    entity_type_id: data.data["entity_type_id"],
                    entity_id: data.data["entity_id"],
                    message: data.data["message"],
                    is_guest: data.data["is_guest"] ?? 0,
                    type: "text",
                    status: 0,
                    is_default_message: 0,
                    is_post_image: data.data.hasOwnProperty("is_post_image")
                        ? data.data["is_post_image"]
                        : 0,
                    is_admin_message: 1
                });

                var notificationMessage = data.data["message"];
            }
            console.log("msg:",msg);
            console.log("data:",data);

            AdminMessage.addMessage(msg, function(chat) {
                console.log("Message output id" +chat['id']);
                console.log(roomMates);
                if(roomMates && roomMates.length > 0){
                    for (let i = 0; i < roomMates.length; i++) {
                        console.log("Room Length 1 " +roomMates[i]);
                        console.log("User Name " +myUsername);
                        if (roomMates[i] != myUsername) {
                            if (users.hasOwnProperty(roomMates[i])) {
                                var toConnection = users[roomMates[i]];
                                if(toConnection && toConnection.length > 0) {
                                    console.log("toConnection : " + toConnection.length);
                                    toConnection.map((e) => {
                                        sendTo(
                                            e.connection,
                                            {
                                                type: "receiveMessage",
                                                data: chat
                                            },
                                            "chat"
                                        );
                                    });
                                }
                            }
                        } else {
                            var fromConnection = users[roomMates[i]];
                            if(fromConnection && fromConnection.length > 0) {
                                console.log("fromConnection : " + fromConnection.length);
                                fromConnection.map((e) => {
                                    console.log("from user "+ roomMates[i]);
                                    console.log(e.device_id);
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "update",
                                            data: chat
                                        },
                                        "chat"
                                    );
                                });
                            }
                        }
                    }
                }
                console.log("after message");
                console.log(chat['id']);
                var entity_id = data.data["entity_type_id"] == 1 ? data.data["entity_id"] : data.data["hospital_id"];

                AdminMessage.sendNotification(
                    data.data["from_user_id"],
                    data.data["entity_type_id"],
                    entity_id,
                    data.data["main_name"],
                    data.data["sub_name"],
                    notificationMessage,
                    chat['id'],
                    data.data["username"],
                    data.data["is_login"],
                    data.data["is_guest"]
                );
            });

            break;*/

        case "sendStaticMessage":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
            } else {
                roomMates = roomMember[combinedUsername2];
            }

            var msg = new Message({
                from_user_id: data.data["from_user_id"],
                to_user_id: data.data["to_user_id"],
                entity_type_id: data.data["entity_type_id"],
                entity_id: data.data["entity_id"],
                message: data.data["message"],
                type: "text",
                status: 0,
                is_default_message: 1,
                is_post_image: data.data.hasOwnProperty("is_post_image")
                    ? data.data["is_post_image"]
                    : 0
            });

            var notificationMessage = data.data["message"];

            Message.addMessage(msg, function(chat) {
                for (let i = 0; i < roomMates.length; i++) {
                    if (roomMates[i] != myUsername) {
                        if (users.hasOwnProperty(roomMates[i])) {
                            var toConnection = users[roomMates[i]];
                            if(toConnection && toConnection.length > 0) {
                                console.log("toConnection : " + toConnection.length);
                                toConnection.map((e) => {
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "receiveMessage",
                                            data: chat
                                        },
                                        "chat"
                                    );
                                });
                            }
                        }
                    } else {
                        var fromConnection = users[roomMates[i]];
                        console.log("fromConnection : " + fromConnection.length);
                        if(fromConnection && fromConnection.length > 0) {
                            fromConnection.map((e) => {
                                console.log("from user "+ roomMates[i]);
                                console.log(e.device_id);
                                sendTo(
                                    e.connection,
                                    {
                                        type: "update",
                                        data: chat
                                    },
                                    "chat"
                                );
                            });
                        }
                    }
                }
            });
            break;

        /*case "update":
            Message.addMessage(data.data, function(chat) {
                if (users.hasOwnProperty(chat.from_user_id)) {
                    var toConnection = users[chat.from_user_id];
                    if(toConnection && toConnection.length > 0) {
                        console.log("toConnection : " + toConnection.length);
                        toConnection.map((e) => {
                            sendTo(
                                e.connection,
                                {
                                    type: "update",
                                    data: chat
                                },
                                "chat"
                            );
                        });
                    }
                }
            });
            break;*/
    }
}

export { chat };
