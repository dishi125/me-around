import { connection } from './db.js';
import { Validation } from '../utils/validation.js';
import { sendTo } from '../server.js';
import bcrypt from 'bcrypt';
import {admin} from '../firebase.js'
import {AWS_URL, public_PATH} from "../config/db.config.js";

// constructor
class User {
    constructor(user) {
        this.firstname = user.firstname;
        this.lastname = user.lastname;
        this.username = user.username;
        this.password = user.password;
        this.createdAt = Date.now();
        this.updatedAt = Date.now();
    }

    static create(newUser, conn) {
        connection.query("INSERT INTO users SET ?", newUser, (err, res) => {
            if (err) {
                console.log("error: ", err);
                const validation = new Validation({
                    message: 'Please contact technical team',
                    status: 500,
                    error: true
                });
                sendTo(conn, validation.convertObjectToJson(), "register");
                return;
            }
            delete newUser.password;
            console.log("created customer: ", { id: res.insertId, ...newUser });
            const validation = new Validation({
                message: '',
                status: 200,
                error: false,
                value: { id: res.insertId, ...newUser }
            });
            sendTo(conn, validation.convertObjectToJson(),"register");
        });
    }

    static checkIfUserExist(newUser, conn) {
        if (Validation.checkIsNotEmpty(newUser.username, "username", conn,"register")
            && Validation.checkIsNotEmpty(newUser.firstname, "firstname", conn,"register")
            && Validation.checkIsNotEmpty(newUser.lastname, "lastname", conn,"register")) {
            connection.query("SELECT * FROM users WHERE username =?", newUser.username, (err, res) => {
                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: 'Please contact technical team',
                        status: 500,
                        error: true
                    });
                    sendTo(conn, validation.convertObjectToJson(),"register");
                } else if (!res.length) {
                    console.log("User create:");
                    User.create(newUser, conn)
                } else {
                    console.log("User already exist");
                    const validation = new Validation({
                        message: 'User already exists',
                        status: 403,
                        error: true
                    });
                    sendTo(conn, validation.convertObjectToJson(),"register");
                }

            });
        }
    }

    static loginUser(user, conn) {
        if (Validation.checkIsNotEmpty(user.username, "username", conn,"login")
            && Validation.checkIsNotEmpty(user.password, "password", conn,"login")) {
            connection.query("SELECT * FROM users WHERE username =?", user.username, (err, res) => {
                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: 'Requested user not found.',
                        status: 404,
                        error: true
                    });
                    sendTo(conn, validation.convertObjectToJson(),"login");
                } else if (res.length > 0) {
                    bcrypt.compare(user.password, res[0].password, function (err, result) {
                        if (result) {
                            console.log("Logged in");
                            delete res[0].password;
                            const validation = new Validation({
                                message: '',
                                status: 200,
                                error: false,
                                value: res[0]
                            });
                            sendTo(conn, validation.convertObjectToJson(),"login");
                        } else {
                            console.log("Logged in failed");
                            const validation = new Validation({
                                message: 'Please check your password again',
                                status: 401,
                                error: true
                            });
                            sendTo(conn, validation.convertObjectToJson(),"login");
                        }
                    });
                } else {
                    console.log("User not found");
                    const validation = new Validation({
                        message: 'Requested user not found.',
                        status: 404,
                        error: true
                    });
                    sendTo(conn, validation.convertObjectToJson(),"login");
                }

            });
        }
    }

    static getAllUsers(id,conn) {
        connection.query("SELECT id,firstname,lastname,username FROM users where id != ?",[id], (err, res) => {
            if (err) {
                console.log(err);
                const validation = new Validation({
                    message: 'Please contact technical team',
                    status: 500,
                    error: true
                });
                sendTo(conn, validation.convertObjectToJson(),"getAllUsers");
            } else if (res.length > 0) {

                const validation = new Validation({
                    message: 'User lists',
                    status: 200,
                    error: false,
                    value: res
                });
                sendTo(conn, validation.convertObjectToJson(),"getAllUsers");

            } else {
                console.log("No Users");
                const validation = new Validation({
                    message: "Don't have any registered users",
                    status: 200,
                    error: true,
                    value: []
                });
                sendTo(conn, validation.convertObjectToJson(),"getAllUsers");
            }

        });

    }

    static async getUsers(to_user_id,from_user_id,entity_type_id,entity_id,main_name,sub_name,message,message_id,username,is_login,is_guest,org_message,message_type,hospital_id,user_id,reply_message_id,timezone) {
        console.log("data in notification");
        let applied_card = await new Promise((resolve, rej) => {
            connection.query("select d.id,d.background_thumbnail,d.character_thumbnail,u.active_level,u.card_level_status,u.id as user_card_id from user_cards u INNER JOIN default_cards_rives d ON u.default_cards_riv_id = d.id where u.user_id = ? and u.is_applied = 1", [from_user_id], (err, res) => {
                if (err) console.log(err);
                resolve(res[0]);
            });
        });
        let background_thumbnail_url = "";
        let character_thumbnail_url = "";
        if (applied_card!=undefined){
            if (applied_card['active_level'] == 1){
                background_thumbnail_url = (applied_card['background_thumbnail']!=null) ? AWS_URL+applied_card['background_thumbnail'] : "";
                if (applied_card['card_level_status'] == "Normal"){
                    character_thumbnail_url = (applied_card['character_thumbnail']!=null) ? AWS_URL+applied_card['character_thumbnail'] : "";
                }
                else {
                    var cardLevelStatusThumb = await new Promise((resolve, rej) => {
                        connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],applied_card['card_level_status'],applied_card['active_level']], (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    character_thumbnail_url = (cardLevelStatusThumb!=undefined) ? AWS_URL+cardLevelStatusThumb['character_thumb'] : "";
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
                    background_thumbnail_url = (level_card['background_thumbnail']!=null) ? AWS_URL+level_card['background_thumbnail'] : "";
                    var card_level_status = (card_level_data['card_level_status']) ? card_level_data['card_level_status'] : "Normal";
                    if (card_level_status == "Normal"){
                        character_thumbnail_url = (level_card['character_thumbnail']!=null) ? AWS_URL+level_card['character_thumbnail'] : "";
                    }
                    else {
                        var defaultCardStatus = await new Promise((resolve, rej) => {
                            connection.query("select character_thumb from card_status_thumbnails where card_id = ? and card_level_status = ? and card_level_id = ?", [applied_card['id'],card_level_status,applied_card['active_level']], (err, res) => {
                                if (err) console.log(err);
                                resolve(res[0]);
                            });
                        });
                        character_thumbnail_url = (defaultCardStatus!=undefined) ? AWS_URL+defaultCardStatus['character_thumb'] : "";
                    }
                }
            }
        }
        let from_user_details = await new Promise((resolve, rej) => {
            connection.query("select avatar,is_character_as_profile from users_detail where user_id = ? limit 1", [from_user_id], (err, res) => {
                if (err) console.log(err);
                resolve(res[0]);
            });
        });
        let from_user_avatar = "";
        let from_is_character_as_profile = "";
        if (from_user_details!=undefined){
            if(from_user_details['avatar']==null){
                from_user_avatar = public_PATH + 'img/avatar/avatar-1.png';
            }
            else {
                from_user_avatar = AWS_URL + from_user_details['avatar'];
            }
            from_is_character_as_profile = from_user_details['is_character_as_profile'];
        }

        // New Code Start
        if(username && username.length){
            var isUser = 0;
            let result = await new Promise((resolve, rej) => {
                connection.query("SELECT * FROM user_entity_relation where entity_type_id = ? and entity_id = ?",[entity_type_id,entity_id], (err, res) => {
                    if (err) console.log(err);
                    resolve(res);
                });
            });
            if(result && result.length){
                isUser = result[0]['user_id'] == from_user_id ? 1 : 0;
            }
            if (entity_type_id==0 && entity_id==0){
                isUser = 1;
            }

            var tokens = [];
            let tokenResult;
            if(is_login == 1 && is_guest == 1){
                tokenResult = await new Promise((resolve, rej) => {
                    connection.query("SELECT * FROM non_login_user_details where id = ? order by id desc",[to_user_id], (err, res) => {
                        if (err) console.log(err);
                        resolve(res);
                    });
                });
            }else{
                tokenResult = await new Promise((resolve, rej) => {
                    connection.query("SELECT * FROM user_devices where user_id = ? order by id desc",[to_user_id], (err, res) => {
                        if (err) console.log(err);
                        resolve(res);
                    });
                });
            }

            if(tokenResult && tokenResult.length){
                Object.keys(tokenResult).forEach(function(key) {
                    var device_token = tokenResult[key].device_token;
                    tokens.push(device_token)
                });
            }
            if(tokens.length){
                var notificationMessage = {
                    notification : {
                        'title' : username,
                        'body' : message,
                    },
                    android : {
                    notification: {
                        sound: 'notifytune.wav',
                    },
                    priority: 'high',
                    },
                    apns : {
                        payload : {
                            aps : {
                                'sound' : 'notifytune.wav'
                            }
                        }
                    },
                    data : {
                        'type' : 'new_message',
                        "click_action" : "FLUTTER_NOTIFICATION_CLICK",
                        "message_id" : message_id.toString(),
                        "messageId" : message_id.toString(),
                        "is_chat" : isUser.toString(),
                        "to_user_id": to_user_id.toString(),
                        "from_user_id": from_user_id.toString(),
                        "message": org_message,
                        "entity_id": entity_id.toString(),
                        "entity_type_id": entity_type_id.toString(),
                        "main_name": main_name,
                        "sub_name": sub_name,
                        "hospital_id": hospital_id.toString(),
                        "user_id": user_id.toString(),
                        "reply_message_id": reply_message_id.toString(),
                        "timezone": timezone,
                        "background_thumbnail_url": background_thumbnail_url,
                        "character_thumbnail_url": character_thumbnail_url,
                        "avatar": from_user_avatar,
                        "is_character_as_profile": from_is_character_as_profile.toString()
                        // "message_type": message_type
                    },
                    tokens : tokens
                }
                // console.dir(notificationMessage, { depth: null });
                admin.messaging().sendMulticast(notificationMessage).then((response) => {
                    // Response is a message ID string.
                    console.log('Successfully sent message');
                    // console.log(response);
                })
                .catch((error) => {
                    console.log('Error sending message:', error);
                });
            }
            // New Code End
        }
        else{
            connection.query("SELECT * FROM users_detail where user_id = ? OR user_id = ? order by id desc",[to_user_id,from_user_id], (err, res) => {
                if (err) {
                    console.log(err);
                    const validation = new Validation({
                        message: 'Please contact technical team',
                        status: 500,
                        error: true
                    });
                }
                else if (res.length > 0) {
                    // console.log("res.length "+res.length);
                    var isUser = 0;
                    var isUserId = 0;
                    connection.query("SELECT * FROM user_entity_relation where entity_type_id = ? and entity_id = ?",[entity_type_id,entity_id], (err1, res1) => {
                        // console.log("response 1" + res1.length)
                        if (err1) {
                            console.log(err1);
                            const validation = new Validation({
                                message: 'Please contact technical team',
                                status: 500,
                                error: true
                            });
                            sendTo(conn, validation.convertObjectToJson(),"getAllUsers");
                        } else if (res1.length > 0) {
                            var isUserId = res1[0]['user_id'];
                            isUser = res1[0]['user_id'] == from_user_id ? 1 : 0;
                        }
                        if (entity_type_id==0 && entity_id==0){
                            isUser = 1;
                        }
                        // console.log("Is user "+ isUser );

                        var token = '';
                        var userName = '';
                        if(to_user_id == from_user_id) {
                            token = res[0].device_token;
                            userName = res[0].name;
                    }else if(res[0].user_id == to_user_id) {
                        // console.log("Is User 1" + isUser);
                        // console.log("Is User id" + isUserId);
                        // console.log("Is User 1 id" + to_user_id);
                        var token = res[0].device_token;
                        var name = entity_type_id == 1 ? main_name+'/'+sub_name : main_name;
                        userName = isUser == 1 ? name : res[1]?.name;
                    } else {

                        // console.log("Is User 2" + isUser);
                        // console.log("Is User 2 id" + isUserId);
                        // console.log("Is User 2 id" + to_user_id);
                            var name = entity_type_id == 1 ? main_name+'/'+sub_name : main_name;
                            token = res[1].device_token;
                            userName = isUser == 1 ? name : res[0].name;
                    }

                    connection.query("SELECT * FROM user_devices where user_id = ? order by id desc",[to_user_id], (err, results) => {
                        var tokens = [];

                        if (err) {
                                console.log(err);
                                const validation = new Validation({
                                    message: 'Please contact technical team',
                                    status: 500,
                                    error: true
                                });
                            }else{
                                Object.keys(results).forEach(function(key) {
                                    var device_token = results[key].device_token;
                                    tokens.push(device_token)
                                });
                                if(tokens.length){
                                    var notificationMessage = {
                                        "notification" : {
                                            'title' : userName,
                                            'body' : message,
                                        },
                                        "android" : {
                                        "notification": {
                                            "sound": 'notifytune.wav',
                                        },
                                        "priority": 'high',
                                        },
                                        "apns" : {
                                            "payload" : {
                                                "aps" : {
                                                    'sound' : 'notifytune.wav'
                                                }
                                            }
                                        },
                                        "data" : {
                                            'type' : 'new_message',
                                            "click_action" : "FLUTTER_NOTIFICATION_CLICK",
                                            "message_id" : message_id.toString(),
                                            "messageId" : message_id.toString(),
                                            "is_chat" : isUser.toString(),
                                            "to_user_id": to_user_id.toString(),
                                            "from_user_id": from_user_id.toString(),
                                            "message": org_message,
                                            "entity_id": entity_id.toString(),
                                            "entity_type_id": entity_type_id.toString(),
                                            "main_name": main_name,
                                            "sub_name": sub_name,
                                            "hospital_id": hospital_id.toString(),
                                            "user_id": user_id.toString(),
                                            "reply_message_id": reply_message_id.toString(),
                                            "timezone": timezone,
                                            "background_thumbnail_url": background_thumbnail_url,
                                            "character_thumbnail_url": character_thumbnail_url,
                                            "avatar": from_user_avatar,
                                            "is_character_as_profile": from_is_character_as_profile.toString()
                                            // "message_type": message_type
                                        },
                                        "tokens" : tokens
                                    }
                                    // console.dir(notificationMessage, { depth: null });
                                    admin.messaging().sendMulticast(notificationMessage).then((response) => {
                                        // Response is a message ID string.
                                        console.log('Successfully sent message');
                                        // console.log(response);
                                    })
                                    .catch((error) => {
                                        console.log('Error sending message:', error);
                                    });
                                }
                            }
                    });
                    });
                }
            });
        }
    }
}

export { User };
