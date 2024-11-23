import { sendTo, users } from "../server.js";
import { GroupMessage } from "../models/group-message.model.js";
import { User } from "../models/user.model.js";
import path from "path";
import fs from "fs";
import moment from "moment";
import mime from "mime";
import {connection, connection as dbConnection} from "../models/db.js";
import {admin} from '../firebase.js'
import { Validation } from '../utils/validation.js';
// const helper = require('../utils/helper');
// const path = require('path');
// const fs = require('fs');

let room = [];
let roomMember = [];
let chatRoomMember = new Map();

async function groupChat(data, connection) {
    switch (data.type) {
        case "initiateGroupChat":
            console.log('initiate Chat');
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var roomId = data.data.country;
            // console.log(data.data.from_user_id);
            // console.log(data.data.to_user_id);
            var combinedUsername = myUsername + to_user_id;
            var combinedUsername2 = to_user_id + myUsername;
            var roomMates = [];
            if (!room.includes(combinedUsername) && !room.includes(combinedUsername2)) {
                room.push(combinedUsername);
                roomMember[combinedUsername] = [];
            }
            if (room.includes(combinedUsername)) {
                roomMates = roomMember[combinedUsername];
                if (!roomMates.includes(myUsername)) {
                    roomMates.push(myUsername);
                }
            } else {
                roomMates = roomMember[combinedUsername2];
                roomMates.push(myUsername);
                roomMember[combinedUsername2] = roomMates;
            }

            chatRoomMember.set(roomId, new Set([]));
            chatRoomMember.get(roomId).add([roomId, to_user_id]);

            // console.log("roomMates: "+roomMates);
            // console.log("chatRoomMember: "+chatRoomMember);
            break;

        case "addCountry":
            var from_user_id = data.data.from_user_id;
            var country = data.data.country;
            dbConnection.query("SELECT * FROM `node_user_countries` WHERE `from_user_id` = ?", [from_user_id],(err, res) => {
                if (err) console.log(err);
                if (res.length > 0){
                    dbConnection.query("UPDATE node_user_countries SET country = ? WHERE from_user_id = ?", [country,from_user_id],(err2, res2) => {
                        if (err2) {
                            console.log("error: ", err2);
                        }
                    })
                }
                else {
                    dbConnection.query(
                        "INSERT INTO node_user_countries (from_user_id,country) VALUES (?,?)", [from_user_id,country],
                        (err3, res3) => {
                            if (err3) {
                                console.log("error: ", err3);
                            }
                        }
                    );
                }
            });
                /*if (users.hasOwnProperty(from_user_id)) {
                    var user = users[from_user_id];
                    if (user && user.length > 0) {
                        Object.keys(user).map(
                            function(object){
                                user[object]["country"] = country;
                            });
                    }
                }*/
            // console.dir(users[from_user_id], { depth: null });
            break;

        case "sendGroupMessage":
            // console.log("inside sendGroupMessage");
            // console.dir(data.data, { depth: null });
            // var myUsername = data.data.from_user_id;
            // var to_user_id = data.data.to_user_id;
            // var combinedUsername = myUsername + to_user_id;
            // var combinedUsername2 = to_user_id + myUsername;

            /*let country_data = await new Promise((resolve, rej) => {
                dbConnection.query("SELECT `id` FROM `countries` WHERE `code` = ? LIMIT 1", [data.data.country],(err, res) => {
                    if (err) console.log(err);
                    resolve(res);
                });
            });
            var country_id = "";
            if (country_data[0] != undefined){
                country_id = country_data[0]['id'];
            }*/

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
                let dir = moment().format("D-M-Y") + "/" + data.data.from_user_id;

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
                    //console.log(e);
                }

                var msg = new GroupMessage({
                    from_user: data.data["from_user_id"],
                    country: data.data.country,
                    message: path.basename("uploads") + "/" + filepath,
                    type: data.data["type"],
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                    is_parent_read: (data.data.message_id==0) ? null : 0
                });

                var sendNotificationMessage = path.basename("uploads") + "/" + filepath;
            } else {
                var msg = new GroupMessage({
                    from_user: data.data["from_user_id"],
                    country: data.data.country,
                    message: data.data["message"],
                    type: data.data["type"],
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                    is_parent_read: (data.data.message_id==0) ? null : 0
                });

                var sendNotificationMessage = data.data["message"];
            }

            // console.log("room_mates___"+roomMates);
            GroupMessage.addMessage(msg, async function (chat) {
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
                console.dir(chat, { depth: null });
                var fromConnection = users[data.data["from_user_id"]];
                if (fromConnection && fromConnection.length > 0){
                    fromConnection.map((e) => {
                        sendTo(
                            e.connection,
                            {
                                type: "update",
                                data: chat
                            },
                            "groupchat"
                        );
                    });
                }

                if (chat['parent_message_user_id']!=undefined) {
                    var parentuserConnection = users[chat['parent_message_user_id']];
                    if (parentuserConnection && parentuserConnection.length > 0) {
                        parentuserConnection.map((e) => {
                            sendTo(
                                e.connection,
                                {
                                    type: "reply_message",
                                    data: true
                                },
                                "groupchat"
                            );
                        });
                    }
                }
            dbConnection.query("SELECT `from_user_id` FROM `node_user_countries` WHERE `country` = ?", [data.data.country],async (err, res) => {
                    if (err) console.log(err);
                    else if (res.length > 0) {
                        res.forEach(element => {
                                var toConnection = users[element['from_user_id']];
                                if (toConnection && toConnection.length > 0) {
                                    /*if (element['from_user_id'] == chat['parent_message_user_id']){
                                        var cnt_reply = 0;
                                        var cnt = 1;
                                        dbConnection.query("SELECT `id` FROM `group_messages` WHERE `from_user` = ? and `country` = ?", [chat['parent_message_user_id'],data.data.country],async (err1, res1) => {
                                            if (err1) console.log(err1);
                                            res1.forEach((element1) => {
                                                    dbConnection.query("SELECT * FROM `group_messages` WHERE `reply_of` = ?", [element1['id']], async (err2, res2) => {
                                                        if (err2) console.log(err2);
                                                        if (res2.length > 0){
                                                            cnt_reply = cnt_reply +res2.length;
                                                        }

                                                        if (cnt == res1.length){
                                                            var userConnection = users[element['from_user_id']];
                                                            if(element['from_user_id'] == data.data.from_user_id){
                                                                if (userConnection && userConnection.length > 0) {
                                                                    userConnection.map((e) => {
                                                                        sendTo(
                                                                            e.connection,
                                                                            {
                                                                                type: "update",
                                                                                data: chat,
                                                                                cnt_reply: cnt_reply
                                                                            },
                                                                            "chat"
                                                                        );
                                                                    });
                                                                }
                                                            }
                                                            else {
                                                                userConnection.map((e) => {
                                                                    sendTo(
                                                                        e.connection,
                                                                        {
                                                                            type: "receiveMessage",
                                                                            data: chat,
                                                                            cnt_reply: cnt_reply
                                                                        },
                                                                        "chat"
                                                                    );
                                                                });
                                                            }
                                                        }
                                                        cnt++;
                                                    })
                                                })
                                        });
                                    }*/
                                    if(element['from_user_id'] != data.data.from_user_id){
                                           /* toConnection.map((e) => {
                                                sendTo(
                                                    e.connection,
                                                    {
                                                        type: "update",
                                                        data: chat
                                                    },
                                                    "chat"
                                                );
                                            });*/
                                    // }
                                    // else {
                                        toConnection.map((e) => {
                                            sendTo(
                                                e.connection,
                                                {
                                                    type: "receiveMessage",
                                                    data: chat
                                                },
                                                "groupchat"
                                            );
                                        });
                                    }
                                }
                        });
                    }

                });

            // Notification for reply message
            if (data.data.message_id!=0 && chat['parent_message_user_id']!=undefined && chat['from_user_id']!=chat['parent_message_user_id']){
                let tokenResult = await new Promise((resolve, rej) => {
                    dbConnection.query("SELECT * FROM user_devices where user_id = ? order by 'id' desc", [chat['parent_message_user_id']], (err, res) => {
                        if (err) console.log(err);
                        resolve(res);
                    });
                });

                var tokens = [];
                if (tokenResult && tokenResult.length) {
                    Object.keys(tokenResult).forEach(function (key) {
                        var device_token = tokenResult[key].device_token;
                        tokens.push(device_token)
                    });
                }

                console.log("tokens.length"+tokens.length);
                if (tokens.length) {
                    var notificationMessage = {
                        notification: {
                            'title': chat['name'],
                            'body': sendNotificationMessage,
                        },
                        android: {
                            notification: {
                                sound: 'notifytune.wav',
                            },
                            priority: 'high',
                        },
                        apns: {
                            payload: {
                                aps: {
                                    'sound': 'notifytune.wav'
                                }
                            }
                        },
                        data: {
                            'type': 'group_message',
                            "click_action": "FLUTTER_NOTIFICATION_CLICK",
                            "message_id": chat['id'].toString(),
                            "messageId": chat['id'].toString(),
                            "is_chat": "1"
                        },
                        tokens: tokens
                    }

                    admin.messaging().sendMulticast(notificationMessage).then((response) => {
                        // Response is a message ID string.
                        console.log('Successfully sent message');
                    }).catch((error) => {
                        console.log('Error sending message:');
                        console.log(error);
                    });
                }
            }

            });
            break;

        case "likeMessage":
            var message_id = data.data.message_id;
            var is_liked = data.data.is_liked;
            var country = data.data.country;

            if (is_liked == 1){
                dbConnection.query("INSERT INTO liked_group_messages (message_id,user_id) values (?,?)", [message_id,data.data.from_user_id],async (err, res) => {
                    if (err) console.log(err);
                })
            }
            else {
                dbConnection.query("DELETE FROM liked_group_messages WHERE message_id = ? and user_id = ?", [message_id,data.data.from_user_id],async (err, res) => {
                    if (err) console.log(err);
                })
            }

            dbConnection.query("SELECT `from_user_id` FROM `node_user_countries` WHERE `country` = ?", [country],async (err1, res1) => {
                if (err1) console.log(err1);
                else if (res1.length > 0) {
                    dbConnection.query(`SELECT u.name,l.user_id FROM liked_group_messages l INNER JOIN users_detail u ON u.user_id = l.user_id where l.message_id = ?`, [message_id], (err2, res2) => {
                        if (err2) console.log(err2);
                        var user_names = [];
                        var user_ids = [];
                        res2.forEach(function(obj){
                            user_names.push(obj.name);
                            user_ids.push(obj.user_id);
                        });
                        // console.dir(user_names, { depth: null });
                        res1.forEach(element => {
                            if (users.hasOwnProperty(element['from_user_id'])) {
                                var toConnection = users[element['from_user_id']];
                                if (toConnection && toConnection.length > 0) {
                                    toConnection.map((e) => {
                                        sendTo(
                                            e.connection,
                                            {
                                                type: "likeMessage",
                                                liked_by: user_names,
                                                message_id: message_id,
                                                is_liked: (user_ids.includes(element['from_user_id']) ==true) ? 1 : 0
                                            },
                                            "groupchat"
                                        );
                                    });
                                }
                            }
                        });
                    })
                }
            });
            break;

        case "removeMessage":
            var country = data.data.country;
            var message_id = data.data.message_id;
            // console.log("country: "+country);
            // console.log("message_id: "+message_id);

            GroupMessage.removeMessage(message_id, async function (data) {
                // console.log("data['affectedRows']: "+data['affectedRows']);
                if (data['affectedRows'] > 0) {
                    dbConnection.query("SELECT `from_user_id` FROM `node_user_countries` WHERE `country` = ?", [country], async (err, res) => {
                        if (err) console.log(err);
                        else if (res.length > 0) {
                            res.forEach(element => {
                                // console.log("element['from_user_id']: "+element['from_user_id']);
                                var toConnection = users[element['from_user_id']];
                                // console.log("toConnection.length: "+toConnection.length);
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
                                            "groupchat"
                                        );
                                    });
                                }
                            });
                        }
                    });
                }
            });
            break;
    }
}

function getAllGroupChats(data, connection){
    // if (data.from_user_id != null && data.to_user_id != null) {
        var search_user_id = (data.search_user_id==undefined) ? 0 : data.search_user_id;
        GroupMessage.getAllGroupChats(data.country, data.page_no, connection, data.user_id, search_user_id, data.timezone)
    /*} else {
        const validation = new Validation({
            message: 'From User Id and To User Id is required.',
            status: 403,
            error: true
        });
        sendTo(connection, validation.convertObjectToJson(),"getAllGroupChats");
    }*/
}

function getAllGroupRepliedChats(data, connection){
    GroupMessage.getAllGroupRepliedChats(data.page_no, connection, data.country, data.user_id, data.timezone)
}

export { groupChat,getAllGroupChats,getAllGroupRepliedChats };
