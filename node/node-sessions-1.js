//import { body } from 'express-validator';

import express from 'express'; // using this style now, because
import session from 'express-session'; // apparently I have connect-redis@9.0.0 as my download
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

app.use(express.urlencoded({ extended: true})); // to get good formatting of input

// [body('username').trim().escape(), body('order').trim().escape()]
// On a public web server, I would want to use something like express-validator to santize input
// because of "trust no data, trust no user"
app.post('/node/node-sessions-1.js',
    (request, response) => {

    request.session.username = request.body.username ?? "person who did not enter their username";
    request.session.order = request.body.order ?? "- well, actually, we aren't sure";
    
    response.set("Content-Type", "text/html");
    response.set("Cache-Control", "no-cache");
    
    response.send("<!doctype html>"
        + "<head><title>Node Sessions Page 1</title></head>"
        + "<body><h1 align=center>Node Sessions Page 1</h1>"
        + "<p>Hello " + request.session.username  + ", "
        + "we here at Evilbucks know that you like ordering "
        + request.session.order + ". We'll sell that info to everyone! "
        + "(cue scary music)</p>"
        + "<a href=\"/node-cgiform.html\">CGI Form</a><br/>"
        + "<form style=\"margin-top:30px\" action = \"/node/node-destroy-session.js\" method = \"post\">"
        + "<button type = \"submit\">Destroy Session</button>"
        + "</form>"
        + "</body></html>");
});

app.get('/node/node-sessions-1.js', (request, response) => {
    response.set("Cache-Control", "no-cache");
    response.send("<!doctype html>"
        + "<head><title>Node Sessions Page 1</title></head>"
        + "<body><h1 align=center>Node Sessions Page 1</h1>"
        + "<p>We here at Evilbucks would like to get some of your data.</p>"
        + "<p>Please navigate back to the form to enter some!</p>"
        + "<a href=\"/node-cgiform.html\">CGI Form</a><br/>"
        + "</body></html>");
});

app.listen(3008, () => {
    console.log("Server listening on port 3008");
});