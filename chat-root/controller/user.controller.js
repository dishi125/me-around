import { User } from '../models/user.model.js';
import { Validation } from '../utils/validation.js';
import bcrypt from 'bcrypt';

export function createUser(data, connection) {
    if (Validation.checkIsNotEmpty(data.password, "Password", connection),"register") {
        var password;
        bcrypt.hash(data.password, 10, function (err, hash) {
            password = hash;
            // Create a Customer
            const user = new User({
                firstname: data.firstname,
                lastname: data.lastname,
                username: data.username,
                password: password
            });

            User.checkIfUserExist(user, connection);
        });
    }
}

export function loginUser(data, connection) {

    const user = new User({
        username: data.username,
        password: data.password
    });

    User.loginUser(user, connection)
}

export function getAllUsers(data, connection) {
    if (data.id != null) {
        User.getAllUsers(data.id, connection)
    } else {
        const validation = new Validation({
            message: 'User id is required.',
            status: 403,
            error: true
        });
        sendTo(connection, validation.convertObjectToJson(),"getAllUsers");
    }
}