import {connection as dbConnection, connection} from "./db.js";
import { Validation } from "../utils/validation.js";
import { sendTo, users } from "../server.js";
import { UPLOADS_PATH, CHAT_PAGINATION_COUNT } from "../config/db.config.js";
import {User} from "./user.model.js";
import moment from "moment";
import 'moment-timezone';
import {admin} from "../firebase.js";

class AdminMessage {
    constructor(message) {
        this.from_user = message.from_user;
        this.to_user = message.to_user;
        this.send_by = message.send_by;
        this.message = message.message;
        this.type = message.type;
        this.reply_of = message.message_id;
        this.created_at = moment().utc().format('YYYY-MM-DD HH:mm:ss');
        this.updated_at = moment().utc().format('YYYY-MM-DD HH:mm:ss');
        this.is_read = message.is_read;
    }

    static addMessage(message, callback) {
        // console.log("Date.now(): "+Date.now());
        // console.log("moment: "+Date.parse(moment().format('YYYY-MM-DD HH:mm:ss')));
        connection.query("INSERT INTO admin_messages SET ?", message, (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }
            if (message["type"] == "file") {
                message["message"] = UPLOADS_PATH + message["message"];
            }

            message["from_user_id"] = message["from_user"];
            message["to_user_id"] = message["to_user"];
            message["time"] = message["created_at"];
            message["status"] = message["is_read"];

            connection.query(
                "SELECT * FROM admin_message_notification_statuses where user_id = ? order by id DESC LIMIT 1",
                [
                    message["to_user_id"]
                ],
                (err1, res1) => {
                    if (err1) {
                        console.log("error: ", err);
                        return;
                    }
                    // console.log("one " + res1.length);
                    if (res1.length > 0) {
                        connection.query(
                            "UPDATE admin_message_notification_statuses SET notification_status = 1 WHERE id =? ",
                            [res1[0]["id"]],
                            (err2, res22) => {
                                if (err2) {
                                    console.log("error: ", err2);
                                    return;
                                }
                            }
                        );
                    } else {
                        connection.query(
                            "INSERT INTO admin_message_notification_statuses (user_id,notification_status) VALUES (?, ?)",
                            [
                                message["to_user_id"],
                                1
                            ],
                            (err3, res3) => {
                                if (err3) {
                                    console.log("error: ", err3);
                                    return;
                                }
                            }
                        );
                    }
                }
            );

            var dbquery = `SELECT * FROM admin_messages where id = ? LIMIT 1`;
            connection.query(dbquery, [message["reply_of"]], (err2, res2) => {
                if (err2) console.log(err2);
                // console.log("res2.length: "+res2.length);
                var parent_user_id = "";
                if (res2.length > 0){
                    message["parent_message"] = res2[0]["message"];
                    if (res2[0]["type"] == "file") {
                        message["parent_message"] = UPLOADS_PATH + res2[0]["message"];
                    }
                    message["parent_message_time"] = res2[0]['created_at'];
                    parent_user_id = res2[0]['from_user'];
                }

                connection.query(`select name from users_detail where user_id = ?`, [parent_user_id], (err3, res3) => {
                    if (err3) console.log(err3);
                    if (message["reply_of"]!=null) {
                        message["parent_message_user"] = (res3[0] == undefined) ? "admin" : res3[0]['name'];
                    }
                    // console.dir(message, { depth: null });
                    callback({ id: res.insertId, ...message });
                })
            });

        });
    }

    static getAdminUsers(){

        let adminUserIds = [];
        for (const UID in users) {
            let tokens = users[UID];
            let isAdmin = false;
            tokens.map((e) => {
                if(e.is_admin_user == 1){
                    isAdmin = true;
                }
            });
            if(isAdmin == true){
                adminUserIds.push(UID);
            }
        }
        return adminUserIds;
    }

    static getAllAdminChats(from, to, pageno, conn, timezone) {
        var totalData;
        var dataPerPage = CHAT_PAGINATION_COUNT;
        var offset = (pageno-1) * dataPerPage;

        connection.query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM admin_messages where (from_user = ? or from_user = ?) and (to_user = ? or to_user = ?) order by id DESC LIMIT ?,?",
            [from, to, from, to, offset,dataPerPage],
            async (err, res) =>  {

                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: "Please contact technical team",
                        status: 500,
                        error: true
                    });
                    sendTo(
                        conn,
                        validation.convertObjectToJson(),
                        "getAllAdminChats"
                    );
                } else if (res.length > 0) {

                    let totalData = await new Promise((resolve, rej) => {
                        connection.query("SELECT FOUND_ROWS() as total", [], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]?.total);
                        });
                    });
                    var total_pages = Math.ceil(totalData / dataPerPage);

                    var cnt = 1;
                    res.forEach(element => {
                        if (element["type"] == "file") {
                            element["message"] =
                                UPLOADS_PATH + element["message"];
                        }
                        element["from_user_id"] = element["from_user"];
                        element["to_user_id"] = element["to_user"];
                        element["status"] = element["is_read"];
                        const inputFormat = 'YYYY-MM-DD HH:mm:ss';
                        const inputTimezone = 'UTC';
                        const outputFormat = 'x';
                        const outputTimezone = (timezone!=undefined) ? timezone : 'Asia/Seoul'; // Target timezone
                        const inputTime = moment(element['created_at']).format('YYYY-MM-DD HH:mm:ss'); // Your input string time
                        // console.log("inputTime "+inputTime);
                        const convertedTime = moment.tz(inputTime, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                        element["time"] = parseInt(convertedTime);
                        const converted_created_at = moment.tz(moment(element['created_at']).format('YYYY-MM-DD HH:mm:ss'), inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                        element["created_at"] = parseInt(converted_created_at);
                        const converted_updated_at = moment.tz(moment(element['updated_at']).format('YYYY-MM-DD HH:mm:ss'), inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                        element["updated_at"] = parseInt(converted_updated_at);

                        var dbquery = `SELECT * FROM admin_messages where id = ? LIMIT 1`;
                        connection.query(dbquery, [element["reply_of"]], (err2, res2) => {
                            if (err2) console.log(err2);
                            // console.log("res2.length: "+res2.length);
                            var parent_user_id = "";
                            if (res2.length > 0){
                                element["parent_message"] = res2[0]["message"];
                                if (res2[0]["type"] == "file") {
                                    element["parent_message"] = UPLOADS_PATH + res2[0]["message"];
                                }
                                const parent_message_time = moment(res2[0]['created_at']).format('YYYY-MM-DD HH:mm:ss');
                                const converted_parent_message_time = moment.tz(parent_message_time, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                element["parent_message_time"] = parseInt(converted_parent_message_time);
                                parent_user_id = res2[0]['from_user'];
                            }

                            connection.query(`select name from users_detail where user_id = ?`, [parent_user_id], (err3, res3) => {
                                if (err3) console.log(err3);
                                if (element["reply_of"]!=null) {
                                    element["parent_message_user"] = (res3[0] == undefined) ? "admin" : res3[0]['name'];
                                }
                                // console.dir(message, { depth: null });
                                if (cnt == res.length){
                                    var returnData = {'total_data' : totalData, 'total_page' : total_pages,'current_page': pageno,'data_per_page': dataPerPage, 'data' : res}
                                    sendTo(
                                        conn,
                                        {
                                            type: "getAllAdminChats",
                                            data: returnData
                                        },
                                        "getAllAdminChats"
                                    );
                                }

                                cnt++;
                            })
                        });
                    });

                } else {
                    console.log("No Chats");
                    const validation = new Validation({
                        message: "Don't have any chat for this users",
                        status: 200,
                        error: true,
                        value: []
                    });
                    sendTo(
                        conn,
                        validation.convertObjectToJson(),
                        "getAllAdminChats"
                    );
                }
            }
        );
    }

    static removeMessage(message_id, callback) {
        connection.query("DELETE FROM admin_messages where id IN (?)", [message_id], (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }

            callback({...res})
        });
    }

    static async sendNotification(user_ids_notification,sendNotificationMessage,chatId,username,to_user_id,from_user_id,user_id,org_message,main_name,sub_name,reply_message_id,timezone){
        console.log("data in notification: ");
        if (user_ids_notification.length) {
            console.log("user_ids_notification: "+user_ids_notification);
            let tokenResult = await new Promise((resolve, rej) => {
                dbConnection.query("SELECT * FROM user_devices where user_id IN (?) order by 'id' desc", [user_ids_notification], (err, res) => {
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
                let from_user_name = "MeAround";
                if (from_user_id!=0){
                    let username = await new Promise((resolve, rej) => {
                        dbConnection.query("SELECT name FROM users_detail where user_id = ? limit 1", [from_user_id], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    from_user_name = (username!=undefined) ? username['name'] : "";
                }

                var notificationMessage = {
                    notification: {
                        'title': username,
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
                        'type': 'new_message',
                        "click_action": "FLUTTER_NOTIFICATION_CLICK",
                        "message_id": chatId,
                        "messageId": chatId,
                        "is_chat": "1",
                        "to_user_id": to_user_id.toString(),
                        "from_user_id": from_user_id.toString(),
                        "message": org_message,
                        "main_name": from_user_name,
                        "sub_name": sub_name,
                        "user_id": user_id.toString(),
                        "reply_message_id": reply_message_id.toString(),
                        "timezone": timezone
                    },
                    tokens: tokens
                }
                // console.dir(notificationMessage, { depth: null });
                admin.messaging().sendMulticast(notificationMessage).then((response) => {
                    // Response is a message ID string.
                    console.log('Successfully sent message');
                    // console.log(response);
                })
                    .catch((error) => {
                        console.log('Error sending message:');
                        console.log(error);
                    });
            }
        }
    }

    static updateReadStatus(from, to, conn, adminroomMates) {
        var timestamp = moment().utc().format('YYYY-MM-DD HH:mm:ss');
        connection.query(
            "UPDATE admin_messages SET is_read = 1 WHERE from_user = ? and to_user = ? and created_at <= ?",
            [to, from, timestamp],
            (err, res) => {
                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: "Please contact technical team",
                        status: 500,
                        error: true
                    });
                    sendTo(
                        conn,
                        validation.convertObjectToJson(),
                        "adminchatUnReadMessages"
                    );
                }
                sendTo(
                    conn,
                    {
                        type: "adminchatUnReadMessages",
                        data: (res["success"] = true)
                    },
                    "adminchatUnReadMessages"
                );
                if (to!=0 && users.hasOwnProperty(to)) {
                    var toConnection = users[to];
                    toConnection.map((e) => {
                        sendTo(
                            e.connection,
                            {
                                type: "adminchatUnReadMessages",
                                data: (res["success"] = true)
                            },
                            "adminchatUnReadMessages"
                        );
                    });
                }
                /*if (from!=0 && users.hasOwnProperty(from)) {
                    var fromConnection = users[from];
                    fromConnection.map((e) => {
                        sendTo(
                            e.connection,
                            {
                                type: "adminchatUnReadMessages",
                                data: (res["success"] = true)
                            },
                            "adminchatUnReadMessages"
                        );
                    });
                }*/

                if (to==0){
                    if (adminroomMates && adminroomMates.length > 0) {
                        for (let i = 0; i < adminroomMates.length; i++) {
                            if (adminroomMates[i] != from) {
                                if (users.hasOwnProperty(adminroomMates[i])) {
                                    var toConnection = users[adminroomMates[i]];
                                    if (toConnection && toConnection.length > 0) {
                                        toConnection.map((e) => {
                                            sendTo(
                                                e.connection,
                                                {
                                                    type: "adminchatUnReadMessages",
                                                    data: (res["success"] = true)
                                                },
                                                "adminchatUnReadMessages"
                                            );
                                        });
                                    }
                                }
                            }
                        }
                    }
                }
            }
        );
    }

}

export { AdminMessage };
