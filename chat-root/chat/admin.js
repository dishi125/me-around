import { sendTo, users } from "../server.js";
import { AdminMessage } from "../models/admin-message.model.js";
import { User } from "../models/user.model.js";
import path from "path";
import fs from "fs";
import moment from "moment";
import 'moment-timezone';
import mime from "mime";
import {connection, connection as dbConnection} from "../models/db.js";
import {admin} from '../firebase.js'
import { Validation } from '../utils/validation.js';
// const helper = require('../utils/helper');
// const path = require('path');
// const fs = require('fs');
import nodemailer from "nodemailer";
import dotenv from "dotenv";
import {Message} from "../models/message.model.js";
dotenv.config({path:'../.env'});
const transporter = nodemailer.createTransport({
    service: 'gmail',
    auth: {
        user: process.env.MAIL_USERNAME,
        pass: process.env.MAIL_PASSWORD
    }
});

let adminroom = [];
let adminroomMember = [];
let newadminroomMember = [];

function adminChat(data, connection) {
    switch (data.type) {
        case "initiateAdminChat":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var user_id = data.data.user_id;
            var combinedUsername = `${myUsername}_${to_user_id}`;
            var combinedUsername2 = `${to_user_id}_${myUsername}`;
            var adminroomMates = [];
            var newadminroomMates = [];
            if (
                !adminroom.includes(combinedUsername) &&
                !adminroom.includes(combinedUsername2)
            ) {
                adminroom.push(combinedUsername);
                adminroomMember[combinedUsername] = [];
                newadminroomMember[combinedUsername] = [];
            }

            if (adminroom.includes(combinedUsername)) {
                adminroomMates = adminroomMember[combinedUsername];
                if (!adminroomMates.includes(myUsername)) {
                    adminroomMates.push(myUsername);
                }
                adminroomMember[combinedUsername] = adminroomMates;

                //New
                newadminroomMates = newadminroomMember[combinedUsername];
                if (!newadminroomMates.includes(user_id)) {
                    newadminroomMates.push(user_id);
                }
                newadminroomMember[combinedUsername] = newadminroomMates
            } else {
                adminroomMates = adminroomMember[combinedUsername2];
                adminroomMates.push(myUsername);
                adminroomMember[combinedUsername2] = adminroomMates;

                // New
                newadminroomMates = newadminroomMember[combinedUsername2];
                newadminroomMates.push(user_id);
                newadminroomMember[combinedUsername2] = newadminroomMates;
            }
            /*    console.log("room:",adminroom);
               console.log("adminroomMates:",adminroomMates);
               console.log("adminroomMember:",adminroomMember);
               console.log("newadminroomMates:",newadminroomMates);
               console.log("newadminroomMember:",newadminroomMember);
               console.log("users:",users);
               */

            //let adminUserIds = AdminMessage.getAdminUsers();

            //console.log(adminUserIds);
            break;

        case "sendAdminMessage":
            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var user_id = data.data.user_id;
            var combinedUsername = `${myUsername}_${to_user_id}`;
            var combinedUsername2 = `${to_user_id}_${myUsername}`;
            var adminroomMates = [];

            console.log("sendAdminMessage");
            //console.log(data.data);
            if (adminroom.includes(combinedUsername)) {
                adminroomMates = newadminroomMember[combinedUsername];
            } else {
                adminroomMates = newadminroomMember[combinedUsername2];
            }

            adminroomMates = adminroomMates && adminroomMates.filter(function(elem, pos) {
                return adminroomMates.indexOf(elem) == pos;
            });

            if (data.data["type"] == "file") {
                var matches = data.data.message.match(/^data:([A-Za-z-+\/]+);base64,(.+)$/),
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
                    //console.log(e);
                }

                var msg = new AdminMessage({
                    from_user: data.data["from_user_id"],
                    to_user: data.data["to_user_id"],
                    send_by: (myUsername == 0) ? user_id : null,
                    message: path.basename("uploads") + "/" + filepath,
                    type: "file",
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                    is_read: 0
                });

                var sendNotificationMessage = path.basename("uploads") + "/" + filepath;
                var message_mail = `<img src="${sendNotificationMessage}" alt="Message" width="100" height="100">`;
            } else {
                var msg = new AdminMessage({
                    from_user: data.data["from_user_id"],
                    to_user: data.data["to_user_id"],
                    send_by: (myUsername == 0) ? user_id : null,
                    message: data.data["message"],
                    type: "text",
                    message_id: (data.data.message_id==0) ? null : data.data.message_id,
                    is_read: 0
                });

                var sendNotificationMessage = data.data["message"];
                var message_mail = sendNotificationMessage;
            }

            AdminMessage.addMessage(msg, async function (chat) {
                const inputFormat = 'YYYY-MM-DD HH:mm:ss';
                const inputTimezone = 'UTC';
                const outputFormat = 'x';
                const outputTimezone = (data.data['timezone']!=undefined) ? data.data['timezone'] : "Asia/Seoul"; // Target timezone
                if (chat['time']!=0){
                    const inputTime = chat["time"]; // Your input string time
                    // console.log("inputTime: "+inputTime);
                    const convertedTime = moment.tz(inputTime, inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                    chat["time"] = parseInt(convertedTime);
                }
                const converted_created_at = moment.tz(chat['created_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                chat["created_at"] = parseInt(converted_created_at);
                const converted_updated_at = moment.tz(chat['updated_at'], inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                chat["updated_at"] = parseInt(converted_updated_at);
                if (chat['parent_message_time']!=undefined){
                    const converted_parent_message_time = moment.tz(moment(chat['parent_message_time']).format('YYYY-MM-DD HH:mm:ss'), inputFormat, inputTimezone).tz(outputTimezone).format(outputFormat);
                    chat["parent_message_time"] = parseInt(converted_parent_message_time);
                }
                // console.dir(chat, { depth: null });

                if (adminroomMates && adminroomMates.length > 0) {
                    console.log(newadminroomMember);
                    console.log(adminroomMates);
                    for (let i = 0; i < adminroomMates.length; i++) {
                        if (adminroomMates[i] != user_id) {
                            if (users.hasOwnProperty(adminroomMates[i])) {
                                var toConnection = users[adminroomMates[i]];
                                if (toConnection && toConnection.length > 0) {
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
                            var fromConnection = users[adminroomMates[i]];
                            console.log(adminroomMates[i]);
                            if (fromConnection && fromConnection.length > 0) {
                                fromConnection.map((e) => {
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

                // Notification
              //  let adminUserIds = AdminMessage.getAdminUsers();
              //"SELECT `id` FROM `users` WHERE `is_admin_access` = 1"
                let adminUserIdsRes = await new Promise((resolve, rej) => {
                    dbConnection.query("SELECT `id` FROM `users` WHERE `is_admin_access` = 1",(err, res) => {
                        if (err) console.log(err);
                        resolve(res);
                    });
                });

                let adminUserIds = [];
                adminUserIdsRes.forEach(element => {
                    adminUserIds.push(element['id']);
                });

                if(myUsername == 0){
                    adminUserIds = adminUserIds.filter(e => e !== user_id);
                }
                if(to_user_id != 0){
                    adminUserIds.push(to_user_id);
                }

                console.log("adminUserIds");
                console.log(adminUserIds);

                if(adminUserIds.length){
                    //check push off for admin users
                    let user_ids_notification = [];
                    if(to_user_id == 0){
                        var cnt_notify = 1;
                        adminUserIds.forEach((val) => {
                            dbConnection.query("SELECT * FROM admin_chat_pin_details where admin_id = ? and chat_user_id = ? and is_pin = 1", [val, data.data["from_user_id"]], (err, res) => {
                                if (err) console.log(err);
                                if (res.length > 0){
                                    dbConnection.query("SELECT * FROM admin_chat_notification_details where admin_id = ? and chat_user_id = ? and is_receive = 0", [val, data.data["from_user_id"]], (err1, res1) => {
                                        if (err1) console.log(err1);
                                        if (res1.length == 0){
                                            user_ids_notification.push(val);
                                        }

                                        if (cnt_notify == adminUserIds.length){
                                            // console.log("send notification "+adminUserIds.length);
                                            // console.log(user_ids_notification);
                                            AdminMessage.sendNotification(user_ids_notification,sendNotificationMessage,chat['id'].toString(),data.data["username"],data.data['to_user_id'],data.data['from_user_id'],data.data['user_id'],data.data['message'],data.data['main_name'],data.data['sub_name'],data.data['message_id'],data.data['timezone']);
                                        }
                                        cnt_notify++;
                                    })
                                }
                                else {
                                    user_ids_notification.push(val);
                                    if (cnt_notify == adminUserIds.length){
                                        // console.log("send notification "+adminUserIds.length);
                                        // console.log(user_ids_notification);
                                        AdminMessage.sendNotification(user_ids_notification,sendNotificationMessage,chat['id'].toString(),data.data["username"],data.data['to_user_id'],data.data['from_user_id'],data.data['user_id'],data.data['message'],data.data['main_name'],data.data['sub_name'],data.data['message_id'],data.data['timezone']);
                                    }
                                    cnt_notify++;
                                }
                            });
                        })
                    }
                    else {
                        user_ids_notification = adminUserIds;
                        // console.log("send notification");
                        // console.log(user_ids_notification);
                        AdminMessage.sendNotification(user_ids_notification,sendNotificationMessage,chat['id'].toString(),data.data["username"],data.data['to_user_id'],data.data['from_user_id'],data.data['user_id'],data.data['message'],data.data['main_name'],data.data['sub_name'],data.data['message_id'],data.data['timezone']);
                    }

                    //send mail to admins
                    if (to_user_id == 0) {
                        let username = await new Promise((resolve, rej) => {
                            dbConnection.query("SELECT name FROM users_detail where user_id = ?", [data.data['user_id']], (err, res) => {
                                if (err) console.log(err);
                                resolve(res);
                            });
                        });
                        if(username[0] != undefined) {
                            username = username[0].name;
                        }

                        let to_emails_res = await new Promise((resolve, rej) => {
                            dbConnection.query("SELECT email FROM users where id IN (?) order by 'id' desc", [adminUserIds], (err, res) => {
                                if (err) console.log(err);
                                resolve(res);
                            });
                        });
                        var to_emails = [];
                        if (to_emails_res && to_emails_res.length) {
                            Object.keys(to_emails_res).forEach(function (key) {
                                var email = to_emails_res[key].email;
                                to_emails.push(email)
                            });
                        }
                        if (to_emails.length) {
                            var mailOptions = {
                                from: process.env.MAIL_FROM_ADDRESS,
                                to: to_emails.join(", "),
                                // to: 'dishita.sojitra@concettolabs.com',
                                subject: username + ' wants to contact to admin',
                                html: `<p>Activated Name: ${data.data["main_name"]}</p><p>Shop Name: ${data.data["sub_name"]}</p><p>Message: ${message_mail}</p>`
                            };
                            transporter.sendMail(mailOptions, function (error, info) {
                                if (error) {
                                    console.log(error);
                                } else {
                                    console.log('Email sent Successfully');
                                }
                            });
                        }
                    }
                }

                if (to_user_id==0){
                    var admin_emails = await new Promise((resolve, rej) => {
                        dbConnection.query("SELECT `value` FROM config where `key` = 'admin_chat' limit 1", (err, res) => {
                            if (err) console.log(err);
                            resolve(res[0]);
                        });
                    });
                    if (admin_emails.value!=undefined) {
                        admin_emails = admin_emails.value.split(',');
                        if (admin_emails.length) {
                            let username = await new Promise((resolve, rej) => {
                                dbConnection.query("SELECT name FROM users_detail where user_id = ?", [data.data['from_user_id']], (err, res) => {
                                    if (err) console.log(err);
                                    resolve(res);
                                });
                            });
                            if (username[0] != undefined) {
                                username = username[0].name;
                            }
                            const first10words = message_mail.split(' ').slice(0, 10).join(" ");
                            var mailOptions = {
                                from: process.env.MAIL_FROM_ADDRESS,
                                to: admin_emails.join(", "),
                                // to: 'dishita.sojitra@concettolabs.com',
                                subject: '(Admin chat) ' + first10words + '/' + username,
                                html: `<p>${message_mail}</p>`
                            };
                            transporter.sendMail(mailOptions, function (error, info) {
                                if (error) {
                                    console.log(error);
                                } else {
                                    console.log('Email sent Successfully');
                                }
                            });
                        }
                    }
                }
            });
            break;

        case "likeMessage":
            var message_id = data.data.message_id;
            var is_liked = data.data.is_liked;

            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var user_id = data.data.user_id;
            var combinedUsername = `${myUsername}_${to_user_id}`;
            var combinedUsername2 = `${to_user_id}_${myUsername}`;
            var adminroomMates = [];
            if (adminroom.includes(combinedUsername)) {
                adminroomMates = newadminroomMember[combinedUsername];
            } else {
                adminroomMates = newadminroomMember[combinedUsername2];
            }
            adminroomMates = adminroomMates && adminroomMates.filter(function(elem, pos) {
                return adminroomMates.indexOf(elem) == pos;
            });

            dbConnection.query("UPDATE admin_messages SET is_liked = ? WHERE id = ?", [is_liked,message_id], (err, res) => {
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

                if (adminroomMates && adminroomMates.length > 0) {
                    for (let i = 0; i < adminroomMates.length; i++) {
                        if (adminroomMates[i] != user_id) {
                            if (users.hasOwnProperty(adminroomMates[i])) {
                                var toConnection = users[adminroomMates[i]];
                                if (toConnection && toConnection.length > 0) {
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
                            var fromConnection = users[adminroomMates[i]];
                            if (fromConnection && fromConnection.length > 0) {
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

            var myUsername = data.data.from_user_id;
            var to_user_id = data.data.to_user_id;
            var combinedUsername = `${myUsername}_${to_user_id}`;
            var combinedUsername2 = `${to_user_id}_${myUsername}`;
            var adminroomMates = [];
            if (adminroom.includes(combinedUsername)) {
                adminroomMates = newadminroomMember[combinedUsername];
            } else {
                adminroomMates = newadminroomMember[combinedUsername2];
            }
            adminroomMates = adminroomMates && adminroomMates.filter(function(elem, pos) {
                return adminroomMates.indexOf(elem) == pos;
            });

            AdminMessage.removeMessage(message_id, async function (data) {
                if (data['affectedRows'] > 0) {
                    if (adminroomMates && adminroomMates.length > 0) {
                        for (let i = 0; i < adminroomMates.length; i++) {
                            var userConnection = users[adminroomMates[i]];
                            if (userConnection && userConnection.length > 0) {
                                userConnection.map((e) => {
                                    sendTo(
                                        e.connection,
                                        {
                                            type: "removeMessage",
                                            data: {
                                                message_id: message_id
                                            }
                                        },
                                        "adminchat"
                                    );
                                });
                            }
                        }
                    }
                }
            });
            break;
    }
}

function getAllAdminChats(data, connection){
    if (data.from_user_id != null && data.to_user_id != null) {
        AdminMessage.getAllAdminChats(data.from_user_id,data.to_user_id,data.page_no, connection, data.timezone)
    } else {
        const validation = new Validation({
            message: 'From User Id and To User Id is required.',
            status: 403,
            error: true
        });
        sendTo(connection, validation.convertObjectToJson(),"getAllChats");
    }
}

function adminchatUnReadMessages(data, connection) {
    if (data.from_user_id != null && data.to_user_id != null) {
        var myUsername = data.from_user_id;
        var to_user_id = data.to_user_id;
        var combinedUsername = `${myUsername}_${to_user_id}`;
        var combinedUsername2 = `${to_user_id}_${myUsername}`;
        var adminroomMates = [];
        if (adminroom.includes(combinedUsername)) {
            adminroomMates = newadminroomMember[combinedUsername];
        } else {
            adminroomMates = newadminroomMember[combinedUsername2];
        }
        adminroomMates = adminroomMates && adminroomMates.filter(function(elem, pos) {
            return adminroomMates.indexOf(elem) == pos;
        });

        console.log("adminchatUnReadMessages adminroomMates: "+adminroomMates);
        AdminMessage.updateReadStatus(data.from_user_id,data.to_user_id, connection, adminroomMates)
    } else {
        const validation = new Validation({
            message: 'From User Id and To User Id is required.',
            status: 403,
            error: true
        });
        sendTo(connection, validation.convertObjectToJson(),"getAllChats");
    }
}

export { adminChat,getAllAdminChats,adminchatUnReadMessages };
