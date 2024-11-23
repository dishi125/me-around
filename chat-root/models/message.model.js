import { connection } from "./db.js";
import { Validation } from "../utils/validation.js";
import { sendTo, users } from "../server.js";
import { UPLOADS_PATH, CHAT_PAGINATION_COUNT } from "../config/db.config.js";
import moment from "moment";
import 'moment-timezone';

class Message {
    constructor(message) {
        this.from_user_id = message.from_user_id;
        this.to_user_id = message.to_user_id;
        this.entity_type_id = message.entity_type_id;
        this.entity_id = message.entity_id;
        this.message = message.message;
        this.type = message.type;
        this.status = message.status;
        this.is_guest = message.is_guest;
        this.is_default_message = message.is_default_message;
        this.is_post_image = message.is_post_image;
        this.reply_of = message.message_id;
        this.created_at = moment().utc().format('x');
        this.updated_at = moment().utc().format('x');
    }

    static addMessage(message, callback) {
        connection.query("INSERT INTO messages SET ?", message, (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }
            if (message["type"] == "file") {
                message["message"] = UPLOADS_PATH + message["message"];
            }

            connection.query(
                "SELECT * FROM messages_notification_status where entity_type_id = ? and entity_id = ? and user_id = ? order by id DESC LIMIT 1",
                [
                    message["entity_type_id"],
                    message["entity_id"],
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
                            "UPDATE messages_notification_status SET notification_status = 1 WHERE id =? ",
                            [res1[0]["id"]],
                            (err2, res22) => {
                                if (err2) {
                                    console.log("error: ", err2);
                                    return;
                                }
                                // console.log("two ");
                            }
                        );
                    } else {
                        connection.query(
                            "INSERT INTO messages_notification_status (entity_type_id,entity_id,user_id,notification_status) VALUES (? , ?, ?, ?)",
                            [
                                message["entity_type_id"],
                                message["entity_id"],
                                message["to_user_id"],
                                1
                            ],
                            (err3, res3) => {
                                if (err3) {
                                    console.log("error: ", err3);
                                    return;
                                }
                                // console.log("three ");
                            }
                        );
                    }
                }
            );

            connection.query(
                "SELECT * FROM messages where entity_type_id = ? and  entity_id = ? and  (from_user_id = ? or from_user_id = ?) and (to_user_id = ? or to_user_id = ?) order by id DESC LIMIT 1,1",
                [
                    message["entity_type_id"],
                    message["entity_id"],
                    message["from_user_id"],
                    message["to_user_id"],
                    message["from_user_id"],
                    message["to_user_id"]
                ],
                (err1, res1) => {
                    if (err1) {
                        console.log("error: ", err);
                        return;
                    }
                    if (res1.length > 0) {
                        var date1 = new Date(res1[0]["created_at"]);
                        var currentDate =
                            date1.getFullYear() +
                            "/" +
                            (date1.getMonth() + 1) +
                            "/" +
                            date1.getDate() +
                            " " +
                            date1.getHours() +
                            ":" +
                            date1.getMinutes();
                        var date2 = new Date(message["created_at"]);
                        var previousDate =
                            date2.getFullYear() +
                            "/" +
                            (date2.getMonth() + 1) +
                            "/" +
                            date2.getDate() +
                            " " +
                            date2.getHours() +
                            ":" +
                            date2.getMinutes();
                        if (
                            currentDate == previousDate &&
                            res1[0]["from_user_id"] == message["from_user_id"]
                        ) {
                            message["time"] = 0;
                        } else {
                            message["time"] = message["created_at"];
                        }
                    } else {
                        message["time"] = message["created_at"];
                    }

                    // console.log("message[reply_of]: "+message["reply_of"]);
                    connection.query(`SELECT m.*,u.name FROM messages m INNER JOIN users_detail u ON u.user_id = m.from_user_id where m.id = ? LIMIT 1`, [message["reply_of"]], (err2, res2) => {
                        if (err2) console.log(err2);
                        // console.log("res2.length: "+res2.length);
                        if (res2.length > 0){
                            message["parent_message"] = res2[0]["message"];
                            if (res2[0]["type"] == "file") {
                                message["parent_message"] = UPLOADS_PATH + res2[0]["message"];
                            }
                            message["parent_message_user"] = res2[0]['name'];
                            message["parent_message_time"] = res2[0]['created_at'];
                        }

                        callback({ id: res.insertId, ...message });
                    });
                }
            );
        });
    }

    static updateMessageStatus(message, callback) {
        connection.query(
            "UPDATE messages SET status =? WHERE id =?",
            [message.id, message.status],
            (err, res) => {
                if (err) {
                    console.log("error: ", err);
                    return;
                }
                if (message["type"] == "file") {
                    message["message"] = UPLOADS_PATH + message["message"];
                }
                console.log("created customer: ", {
                    id: res.insertId,
                    ...message
                });
                callback({ id: res.insertId, ...message });
            }
        );
    }

    static getAllChats(entity_type_id, entity_id, from, to, pageno, conn,  is_guest = 0,timezone) {
        var totalData;
        var dataPerPage = CHAT_PAGINATION_COUNT;
        var offset = (pageno-1) * dataPerPage;

        connection.query(
            "SELECT SQL_CALC_FOUND_ROWS * FROM messages where entity_type_id = ? and  entity_id = ? and (from_user_id = ? or from_user_id = ?) and (to_user_id = ? or to_user_id = ?) and is_guest = ? order by id DESC LIMIT ?,?",
            [entity_type_id, entity_id, from, to, from, to,is_guest, offset,dataPerPage],
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
                        "getAllChats"
                    );
                } else if (res.length > 0) {

                    let totalData = await new Promise((resolve, rej) => {
                        connection.query("SELECT FOUND_ROWS() as total", [], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]?.total);
                        });
                    });
                    var total_pages = Math.ceil(totalData / dataPerPage);

                    let to_user_language = await new Promise((resolve, rej) => {
                        connection.query("SELECT * from chat_languages where user_id = ? and type = 'chat' limit 1", [to], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    let language = (to_user_language!=undefined) ? to_user_language['language'] : "ko";

                    for (var i = 0; i < res.length; i++) {
                        var date1 = new Date(res[i]["created_at"]);
                        var currentDate =
                            date1.getFullYear() +
                            "/" +
                            (date1.getMonth() + 1) +
                            "/" +
                            date1.getDate() +
                            " " +
                            date1.getHours() +
                            ":" +
                            date1.getMinutes();
                        if (i > 0) {
                            var date2 = new Date(res[i - 1]["created_at"]);
                            var previousDate =
                                date2.getFullYear() +
                                "/" +
                                (date2.getMonth() + 1) +
                                "/" +
                                date2.getDate() +
                                " " +
                                date2.getHours() +
                                ":" +
                                date2.getMinutes();
                            if (
                                currentDate == previousDate &&
                                res[i - 1]["from_user_id"] ==
                                res[i]["from_user_id"]
                            ) {
                                res[i]["time"] = 0;
                            } else {
                                res[i]["time"] = res[i]["created_at"];
                            }
                        } else {
                            res[i]["time"] = res[i]["created_at"];
                        }
                    }

                    var cnt = 1;
                    res.forEach(element => {
                        if (element["type"] == "file") {
                            element["message"] = UPLOADS_PATH + element["message"];
                        }
                        const inputFormat = 'x';
                        const inputTimezone = 'UTC';
                        const outputFormat = 'x';
                        const outputTimezone = (timezone!=undefined) ? timezone : 'Asia/Seoul'; // Target timezone
                        if (element['time']!=0){
                            const inputTime = element['time']; // Your input string time
                            // console.log("timezone "+timezone);
                            const convertedTime = moment.tz(inputTime, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                            element["time"] = parseInt(convertedTime);
                        }
                        const converted_created_at = moment.tz(element['created_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                        element["created_at"] = parseInt(converted_created_at);
                        const converted_updated_at = moment.tz(element['updated_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                        element["updated_at"] = parseInt(converted_updated_at);

                        connection.query(`SELECT m.*,u.name FROM messages m INNER JOIN users_detail u ON u.user_id = m.from_user_id where m.id = ? LIMIT 1`, [element['reply_of']], (err1, res1) => {
                            if (err1) console.log(err1);
                            if (res1.length > 0){
                                element["parent_message"] = res1[0]["message"];
                                if (res1[0]["type"] == "file") {
                                    element["parent_message"] = UPLOADS_PATH + res1[0]["message"];
                                }
                                element["parent_message_user"] = res1[0]['name'];
                                element["parent_message_time"] = res1[0]['created_at'];
                                const converted_parent_message_time = moment.tz(element['parent_message_time'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                element["parent_message_time"] = parseInt(converted_parent_message_time);
                            }

                            if (cnt == res.length){
                                var returnData = {'total_data' : totalData, 'total_page' : total_pages,'current_page': pageno,'data_per_page': dataPerPage, 'data' : res, 'language': language}
                                sendTo(
                                    conn,
                                    {
                                        type: "getAllChats",
                                        data: returnData
                                    },
                                    "getAllChats"
                                );
                            }

                            cnt++;
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
                        "getAllChats"
                    );
                }
            }
        );


    }

    static updateReadStatus(entity_type_id, entity_id, from, to, conn, is_guest = 0) {
        var timestamp = Date.now();
        connection.query(
            "UPDATE messages SET status = 1 WHERE entity_type_id=? and entity_id=? and  from_user_id=? and to_user_id=? and created_at <= ? and is_guest = ? ",
            [entity_type_id, entity_id, to, from, timestamp, is_guest],
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
                        "checkUnReadMessages"
                    );
                }
                sendTo(
                    conn,
                    {
                        type: "checkUnReadMessages",
                        data: (res["success"] = true)
                    },
                    "checkUnReadMessages"
                );
                console.log("from  " + to);
                if (users.hasOwnProperty(to)) {
                    console.log("from  " + 1111111);
                    var toConnection = users[to];
                    toConnection.map((e) => {
                        // console.log(e);
                        sendTo(
                            e.connection,
                            {
                                type: "checkUnReadMessagesResponse",
                                data: (res["success"] = true)
                            },
                            "checkUnReadMessages"
                        );
                    });
                } else {
                    console.log("to  " + 87787);
                }
            }
        );
    }

    static removeMessage(message_id, callback) {
        connection.query("DELETE FROM messages where id IN (?)", [message_id], (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }

            callback({...res})
        });
    }
}

export { Message };
