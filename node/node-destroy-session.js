import express from 'express'; // using this style now, because
import  session from 'express-session'; // apparently I have connect-redis@9.0.0 as my download
import {createClient} from 'redis';
import {RedisStore} from 'connect-redis';


const app = express();
const redisClient = createClient();
await redisClient.connect();

app.use(session({
    store: new RedisStore({client: redisClient}),
    secret: "b2k3*23H^4r3Dewvs5Hvks3452",
    resave: false,
    saveUninitialized: false,
    cookie: {path: '/node/', secure: false}
}));

app.post('/node/node-destroy-session.js', (request, response) => {
    request.session.destroy(err => {
        if (err) {
            return response.status(500).send("Encountered an error while destroying session");
        }
        response.clearCookie('connect.sid', {path: '/node/'});
        response.set("Content-Type", "text/html");
        response.set("Cache-Control", "no-cache");
        response.send("<!doctype html>"
            + "<head><title>Node Session DESTROYED</title></head>"
            + "<body><h1 align=center>Node Session DESTROYED</h1>"
            + "<p>Your data is gone...</p>"
            + "<a href=\"/node/node-sessions-1.js\">Session Page 1</a><br/>"
            + "<a href=\"/node-cgiform.html\">CGI Form</a><br/>"
            + "</body></html>");
    });
});

app.listen(3009, () => {
    console.log("Server listening on port 3009");
});