import {connection as dbConnection, connection} from "./db.js";
import { Validation } from "../utils/validation.js";
import { sendTo, users } from "../server.js";
import {UPLOADS_PATH, CHAT_PAGINATION_COUNT, public_PATH, AWS_URL} from "../config/db.config.js";
import {User} from "./user.model.js";
import moment from "moment";
import 'moment-timezone';
import {getAllGroupChats} from "../chat/group.js";
import {response} from "express";

class GroupMessage {
    constructor(message) {
        this.from_user = message.from_user;
        this.country = message.country;
        this.message = message.message;
        this.type = message.type;
        this.reply_of = message.message_id;
        this.is_parent_read = message.is_parent_read;
        this.created_at = moment().utc().format('x');
        this.updated_at = moment().utc().format('x');
    }

    static addMessage(message, callback) {
        connection.query("INSERT INTO group_messages SET ?", message, async (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }
            if (message["type"] == "file") {
                message["message"] = UPLOADS_PATH + message["message"];
            }
            message["from_user_id"] = message["from_user"];
            message["time"] = message["created_at"];
            let applied_card = await new Promise((resolve, rej) => {
                connection.query("select d.id,d.background_thumbnail,d.character_thumbnail,u.active_level,u.card_level_status,u.id as user_card_id from user_cards u INNER JOIN default_cards_rives d ON u.default_cards_riv_id = d.id where u.user_id = ? and u.is_applied = 1", [message["from_user"]], (err, res) => {
                    if (err) console.log(err);
                    resolve(res[0]);
                });
            });
            message['background_thumbnail_url'] = "";
            message['character_thumbnail_url'] = "";
            if (applied_card!=undefined){
                if (applied_card['active_level'] == 1){
                    message['background_thumbnail_url'] = (applied_card['background_thumbnail']!=null) ? AWS_URL+applied_card['background_thumbnail'] : "";
                    if (applied_card['card_level_status'] == "Normal"){
                        message['character_thumbnail_url'] = (applied_card['character_thumbnail']!=null) ? AWS_URL+applied_card['character_thumbnail'] : "";
                    }
                    else {
                        var cardLevelStatusThumb = await new Promise((resolve, rej) => {
                            connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],applied_card['card_level_status'],applied_card['active_level']], (err, res) => {
                                if (err) console.log(err);
                                resolve(res[0]);
                            });
                        });
                        message['character_thumbnail_url'] = (cardLevelStatusThumb!=undefined) ? AWS_URL+cardLevelStatusThumb['character_thumb'] : "";
                    }
                }
                else {
                    let card_level_data = await new Promise((resolve, rej) => {
                        connection.query("select card_level_status from user_card_levels where user_card_id = ? and card_level = ?", [applied_card['user_card_id'],applied_card['active_level']], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    let level_card = await new Promise((resolve, rej) => {
                        connection.query("select background_thumbnail,character_thumbnail from card_level_details where main_card_id = ? and card_level = ?", [applied_card['id'],applied_card['active_level']], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    if (level_card!=undefined){
                        message['background_thumbnail_url'] = (level_card['background_thumbnail']!=null) ? AWS_URL+level_card['background_thumbnail'] : "";
                        var card_level_status = (card_level_data['card_level_status']) ? card_level_data['card_level_status'] : "Normal";
                        if (card_level_status == "Normal"){
                            message['character_thumbnail_url'] = (level_card['character_thumbnail']!=null) ? AWS_URL+level_card['character_thumbnail'] : "";
                        }
                        else {
                            var defaultCardStatus = await new Promise((resolve, rej) => {
                                connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],card_level_status,applied_card['active_level']], (err, res) => {
                                    if (err) console.log(err);
                                    resolve(res[0]);
                                });
                            });
                            message['character_thumbnail_url'] = (defaultCardStatus!=undefined) ? AWS_URL+defaultCardStatus['character_thumb'] : "";
                        }
                    }
                }
            }
            // message["created_at"] = parseInt(moment(message["created_at"]).format('x'));
            // message["updated_at"] = parseInt(moment(message["updated_at"]).format('x'));
            connection.query("select name,avatar,is_character_as_profile from users_detail where user_id = ? limit 1", [message["from_user"]], (err1, res1) => {
                if (err1) {
                    console.log("error: ", err1);
                    return;
                }

                message['avatar'] = public_PATH + 'img/avatar/avatar-1.png';
                message["name"] = "";
                if (res1.length > 0){
                    message["name"] = res1[0]['name'];
                    if(res1[0]['avatar']==null){
                        message['avatar'] = public_PATH + 'img/avatar/avatar-1.png';
                    }
                    else {
                        message['avatar'] = AWS_URL + res1[0]['avatar'];
                    }
                    message["is_character_as_profile"] = res1[0]['is_character_as_profile'];
                }

                connection.query(`SELECT g.message,g.type,g.created_at,g.from_user,u.name FROM group_messages g INNER JOIN users_detail u ON u.user_id = g.from_user where g.id = ? LIMIT 1`, [message["reply_of"]], (err2, res2) => {
                    if (err2) console.log(err2);
                    if (res2.length > 0){
                        message["parent_message"] = res2[0]["message"];
                        if (res2[0]["type"] == "file") {
                            message["parent_message"] = UPLOADS_PATH + res2[0]["message"];
                        }
                        message["parent_message_user"] = res2[0]['name'];
                        message["parent_message_time"] = res2[0]['created_at'];
                        message["parent_message_user_id"] = res2[0]['from_user'];
                    }

                    callback({ id: res.insertId, ...message });
                });

            });
        });
    }

    static removeMessage(message_id, callback) {
        connection.query("DELETE FROM group_messages where id IN (?)", [message_id], (err, res) => {
            if (err) {
                console.log("error: ", err);
                return;
            }

            callback({...res})
        });
    }

    static getAllGroupChats(country, pageno, conn, user_id, search_user_id, timezone) {
        var totalData;
        var dataPerPage = CHAT_PAGINATION_COUNT;
        var offset = (pageno-1) * dataPerPage;

        var dbquery;
        if (search_user_id == 0) {
            dbquery = `SELECT SQL_CALC_FOUND_ROWS g.*,u.name, u.avatar, u.is_character_as_profile
                           FROM group_messages g
                                    INNER JOIN users_detail u ON u.user_id = g.from_user
                           where g.country = ?
                           order by g.id DESC LIMIT ?,?`;
        }
        else {
            dbquery = `SELECT SQL_CALC_FOUND_ROWS g.*,u.name, u.avatar, u.is_character_as_profile
                           FROM group_messages g
                                    INNER JOIN users_detail u ON u.user_id = g.from_user
                           where g.country = ? and g.from_user = ${search_user_id}
                           order by g.id DESC LIMIT ?,?`;
        }

        connection.query(dbquery, [country,offset,dataPerPage], async (err, res) =>  {
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
                        "getAllGroupChats"
                    );
                }
                else if (res.length > 0) {
                    let totalData = await new Promise((resolve, rej) => {
                        connection.query("SELECT FOUND_ROWS() as total", [], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]?.total);
                        });
                    });
                    var total_pages = Math.ceil(totalData / dataPerPage);

                    for (var i = 0; i < res.length; i++) {
                        /*var date1 = new Date(res[i]["created_at"]);
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
                                res[i - 1]["from_user"] ==
                                res[i]["from_user"]
                            ) {
                                res[i]["time"] = 0;
                            } else {
                                res[i]["time"] = res[i]["created_at"];
                            }
                        } else {*/
                            res[i]["time"] = res[i]["created_at"];
                        // }
                    }

                    var cnt = 1;
                    res.forEach(async (element) => {
                        if (element["type"] == "file") {
                            element["message"] = UPLOADS_PATH + element["message"];
                        }
                        element["from_user_id"] = element["from_user"];
                        // element["created_at"] = parseInt(moment(element["created_at"]).format('x'));
                        // element["updated_at"] = parseInt(moment(element["updated_at"]).format('x'));
                        if(element['avatar']==null){
                            element['avatar'] = public_PATH + 'img/avatar/avatar-1.png';
                        }
                        else {
                            element['avatar'] = AWS_URL + element['avatar'];
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


                        let applied_card = await new Promise((resolve, rej) => {
                            connection.query("select d.id,d.background_thumbnail,d.character_thumbnail,u.active_level,u.card_level_status,u.id as user_card_id from user_cards u INNER JOIN default_cards_rives d ON u.default_cards_riv_id = d.id where u.user_id = ? and u.is_applied = 1", [element["from_user"]], (err, res) => {
                                if (err) console.log(err);
                                resolve(res[0]);
                            });
                        });
                        // console.log("applied_card");
                        element['background_thumbnail_url'] = "";
                        element['character_thumbnail_url'] = "";
                        if (applied_card!=undefined){
                            if (applied_card['active_level'] == 1){
                                element['background_thumbnail_url'] = (applied_card['background_thumbnail']!=null) ? AWS_URL+applied_card['background_thumbnail'] : "";
                                if (applied_card['card_level_status'] == "Normal"){
                                    element['character_thumbnail_url'] = (applied_card['character_thumbnail']!=null) ? AWS_URL+applied_card['character_thumbnail'] : "";
                                }
                                else {
                                    var cardLevelStatusThumb = await new Promise((resolve, rej) => {
                                        connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],applied_card['card_level_status'],applied_card['active_level']], (err, res) => {
                                            if (err) console.log(err);
                                            resolve(res[0]);
                                        });
                                    });
                                    // console.log("cardLevelStatusThumb");
                                    element['character_thumbnail_url'] = (cardLevelStatusThumb!=undefined) ? AWS_URL+cardLevelStatusThumb['character_thumb'] : "";
                                }
                            }
                            else {
                                let card_level_data = await new Promise((resolve, rej) => {
                                    connection.query("select card_level_status from user_card_levels where user_card_id = ? and card_level = ?", [applied_card['user_card_id'],applied_card['active_level']], (err, res) => {
                                        if (err) console.log(err);
                                        resolve(res[0]);
                                    });
                                });
                                // console.log("card_level_data");
                                let level_card = await new Promise((resolve, rej) => {
                                    connection.query("select background_thumbnail,character_thumbnail from card_level_details where main_card_id = ? and card_level = ?", [applied_card['id'],applied_card['active_level']], (err, res) => {
                                        if (err) console.log(err);
                                        resolve(res[0]);
                                    });
                                });
                                // console.log("level_card");
                                if (level_card!=undefined){
                                    element['background_thumbnail_url'] = (level_card['background_thumbnail']!=null) ? AWS_URL+level_card['background_thumbnail'] : "";
                                    var card_level_status = (card_level_data['card_level_status']) ? card_level_data['card_level_status'] : "Normal";
                                    if (card_level_status == "Normal"){
                                        element['character_thumbnail_url'] = (level_card['character_thumbnail']!=null) ? AWS_URL+level_card['character_thumbnail'] : "";
                                    }
                                    else {
                                        var defaultCardStatus = await new Promise((resolve, rej) => {
                                            connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],card_level_status,applied_card['active_level']], (err, res) => {
                                                if (err) console.log(err);
                                                resolve(res[0]);
                                            });
                                        });
                                        // console.log("defaultCardStatus");
                                        element['character_thumbnail_url'] = (defaultCardStatus!=undefined) ? AWS_URL+defaultCardStatus['character_thumb'] : "";
                                    }
                                }
                            }
                        }
                        // console.log("after applied_card");

                        connection.query(`SELECT g.message,g.type,g.created_at,u.name FROM group_messages g INNER JOIN users_detail u ON u.user_id = g.from_user where g.country = ? and g.id = ? LIMIT 1`, [country,element['reply_of']], (err1, res1) => {
                            if (err1) console.log(err1);
                            // console.log("res1.length: "+res1.length);
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

                            dbConnection.query(`SELECT u.name FROM liked_group_messages l INNER JOIN users_detail u ON u.user_id = l.user_id where l.message_id = ?`, [element['id']], (err2, res2) => {
                                if (err2) console.log(err2);
                                var user_names = [];
                                res2.forEach(function(obj){
                                    user_names.push(obj.name);
                                });
                                // console.dir(user_names, { depth: null });
                               element['liked_by'] = user_names;

                                if (cnt == res.length){
                                    var cnt_reply = 0;
                                    var cnt1 = 1;
                                    dbConnection.query("SELECT `id` FROM `group_messages` WHERE `from_user` = ? and `country` = ?", [user_id,country],async (err3, res3) => {
                                        if (err3) console.log(err3);
                                        if (res3.length > 0) {
                                            res3.forEach((element3) => {
                                                dbConnection.query("SELECT * FROM `group_messages` WHERE `reply_of` = ? and `is_parent_read` = 0", [element3['id']], async (err4, res4) => {
                                                    if (err4) console.log(err4);
                                                    if (res4.length > 0) {
                                                        cnt_reply = cnt_reply + res4.length;
                                                    }

                                                    if (cnt1 == res3.length){
                                                        var returnData = {'total_data' : totalData, 'total_page' : total_pages,'current_page': pageno,'data_per_page': dataPerPage, 'data' : res, 'cnt_reply': cnt_reply}
                                                        sendTo(
                                                            conn,
                                                            {
                                                                type: "getAllGroupChats",
                                                                data: returnData
                                                            },
                                                            "getAllGroupChats"
                                                        );
                                                    }
                                                    cnt1++;
                                                })
                                            })
                                        }
                                        else {
                                            var returnData = {'total_data' : totalData, 'total_page' : total_pages,'current_page': pageno,'data_per_page': dataPerPage, 'data' : res, 'cnt_reply': cnt_reply}
                                            sendTo(
                                                conn,
                                                {
                                                    type: "getAllGroupChats",
                                                    data: returnData
                                                },
                                                "getAllGroupChats"
                                            );
                                        }
                                    });
                                }

                                cnt++;
                            })
                        });
                    });

                    /*var returnData = {'total_data' : totalData, 'total_page' : total_pages,'current_page': pageno,'data_per_page': dataPerPage, 'data' : res}
                    sendTo(
                        conn,
                        {
                            type: "getAllGroupChats",
                            data: returnData
                        },
                        "getAllGroupChats"
                    );*/
                }
                else {
                    console.log("No Chats");
                    const validation = new Validation({
                        message: "Don't have any chat for this group",
                        status: 200,
                        error: true,
                        value: []
                    });
                    sendTo(
                        conn,
                        validation.convertObjectToJson(),
                        "getAllGroupChats"
                    );
                }
            }
        );
    }

    static getAllGroupRepliedChats(pageno, conn, country, user_id, timezone){
        connection.query(`select g.*,u.name from group_messages g INNER JOIN users_detail u ON u.user_id = g.from_user where g.from_user = ? and g.country = ? order by g.id DESC`, [user_id,country], (err, res) =>  {
                // console.log("res.length "+res.length);
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
                        "getAllGroupRepliedChats"
                    );
                }
                else if (res.length > 0) {
                    const list_reply = [];
                    var cnt = 1;
                    res.forEach((element) => {
                        if (element["type"] == "file") {
                            element["message"] = UPLOADS_PATH + element["message"];
                        }

                        connection.query(`select g.*,u.name,u.avatar,u.is_character_as_profile FROM group_messages g INNER JOIN users_detail u ON u.user_id = g.from_user where reply_of = ?`, [element['id']], async (err1, res1) => {
                            if (err1) console.log(err1);
                            if (res1.length > 0) {
                                const collection = await Promise.all(
                                    res1.map(async (element1) => {
                                        if (element1['avatar'] == null) {
                                            element1['avatar'] = public_PATH + 'img/avatar/avatar-1.png';
                                        } else {
                                            element1['avatar'] = AWS_URL + element1['avatar'];
                                        }

                                        if (element1["type"] == "file") {
                                            element1["message"] = UPLOADS_PATH + element1["message"];
                                        }

                                        let applied_card = await new Promise((resolve, rej) => {
                                            connection.query("select d.id,d.background_thumbnail,d.character_thumbnail,u.active_level,u.card_level_status,u.id as user_card_id from user_cards u INNER JOIN default_cards_rives d ON u.default_cards_riv_id = d.id where u.user_id = ? and u.is_applied = 1", [element1["from_user"]], (err, res) => {
                                                if (err) console.log(err);
                                                resolve(res[0]);
                                            });
                                        });
                                        element1['background_thumbnail_url'] = "";
                                        element1['character_thumbnail_url'] = "";
                                        if (applied_card!=undefined){
                                            if (applied_card['active_level'] == 1){
                                                element1['background_thumbnail_url'] = (applied_card['background_thumbnail']!=null) ? AWS_URL+applied_card['background_thumbnail'] : "";
                                                if (applied_card['card_level_status'] == "Normal"){
                                                    element1['character_thumbnail_url'] = (applied_card['character_thumbnail']!=null) ? AWS_URL+applied_card['character_thumbnail'] : "";
                                                }
                                                else {
                                                    var cardLevelStatusThumb = await new Promise((resolve, rej) => {
                                                        connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],applied_card['card_level_status'],applied_card['active_level']], (err, res) => {
                                                            if (err) console.log(err);
                                                            resolve(res[0]);
                                                        });
                                                    });
                                                    element1['character_thumbnail_url'] = (cardLevelStatusThumb!=undefined) ? AWS_URL+cardLevelStatusThumb['character_thumb'] : "";
                                                }
                                            }
                                            else {
                                                let card_level_data = await new Promise((resolve, rej) => {
                                                    connection.query("select card_level_status from user_card_levels where user_card_id = ? and card_level = ?", [applied_card['user_card_id'],applied_card['active_level']], (err, res) => {
                                                        if (err) console.log(err);
                                                        resolve(res[0]);
                                                    });
                                                });
                                                let level_card = await new Promise((resolve, rej) => {
                                                    connection.query("select background_thumbnail,character_thumbnail from card_level_details where main_card_id = ? and card_level = ?", [applied_card['id'],applied_card['active_level']], (err, res) => {
                                                        if (err) console.log(err);
                                                        resolve(res[0]);
                                                    });
                                                });
                                                if (level_card!=undefined){
                                                    element1['background_thumbnail_url'] = (level_card['background_thumbnail']!=null) ? AWS_URL+level_card['background_thumbnail'] : "";
                                                    var card_level_status = (card_level_data['card_level_status']) ? card_level_data['card_level_status'] : "Normal";
                                                    if (card_level_status == "Normal"){
                                                        element1['character_thumbnail_url'] = (level_card['character_thumbnail']!=null) ? AWS_URL+level_card['character_thumbnail'] : "";
                                                    }
                                                    else {
                                                        var defaultCardStatus = await new Promise((resolve, rej) => {
                                                            connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],card_level_status,applied_card['active_level']], (err, res) => {
                                                                if (err) console.log(err);
                                                                resolve(res[0]);
                                                            });
                                                        });
                                                        element1['character_thumbnail_url'] = (defaultCardStatus!=undefined) ? AWS_URL+defaultCardStatus['character_thumb'] : "";
                                                    }
                                                }
                                            }
                                        }

                                        const inputFormat = 'x';
                                        const inputTimezone = 'UTC';
                                        const outputFormat = 'x';
                                        const outputTimezone = (timezone!=undefined) ? timezone : 'Asia/Seoul'; // Target timezone
                                        if (element1['created_at']!=0){
                                            const inputTime = element1['created_at']; // Your input string time
                                            // console.log("timezone "+timezone);
                                            const convertedTime = moment.tz(inputTime, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                            element1["time"] = parseInt(convertedTime);
                                        }
                                        const converted_created_at = moment.tz(element1['created_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                        element1["created_at"] = parseInt(converted_created_at);
                                        const converted_updated_at = moment.tz(element1['updated_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                        element1["updated_at"] = parseInt(converted_updated_at);
                                        const converted_parent_message_time = moment.tz(element['created_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                                        element1["parent_message_time"] = parseInt(converted_parent_message_time);

                                        list_reply.push({
                                            ...element1,
                                            from_user_id: element1['from_user'],
                                            parent_message: element['message'],
                                            parent_message_user: element['name'],
                                        })

                                        return element1;
                                    })
                                );
                            }

                            let update_read_status = await new Promise((resolve, rej) => {
                                connection.query("UPDATE group_messages SET is_parent_read = 1 WHERE reply_of = ?", [element["id"]], (err, res) => {
                                    if (err) console.log(err);
                                    resolve(res);
                                });
                            });

                            if (cnt == res.length) {
                                // console.dir(list_reply, { depth: null });
                                sendTo(
                                    conn,
                                    {
                                        type: "getAllGroupRepliedChats",
                                        data: list_reply
                                    },
                                    "getAllGroupRepliedChats"
                                );
                            }
                            cnt++;
                        })
                    })
                }
                else {
                    console.log("No Chats");
                    const validation = new Validation({
                        message: "Don't have any chat for this group",
                        status: 200,
                        error: true,
                        value: []
                    });
                    sendTo(
                        conn,
                        {
                            type: "getAllGroupRepliedChats",
                            data: []
                        },
                        "getAllGroupRepliedChats"
                    );
                }
            }
        );
    }

}

export { GroupMessage };
