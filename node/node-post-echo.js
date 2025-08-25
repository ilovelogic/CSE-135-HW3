//import { body } from 'express-validator';

import express from 'express'; // importing Express module

const app = express(); // new Express application

app.use(express.urlencoded({extended: true}));

// [body('username').trim().escape()],
// On a public web server, I would want to use something like express-validator to santize input
// because of "trust no data, trust no user"
app.post('/node/node-post-echo.js', (request, response) => { // called when a get received at the url
    response.set("Content-Type", "text/html");
    response.set("Cache-Control", "no-cache");
    
    response.send("<!doctype html>"
        + "<head><title>Post Echo</title></head>"
        + "<body><h1 align=center>Hello " + request.body.username + "</h1>"
        + "</body></html>");
});

app.listen(3006, () => {
    console.log('Server running on port 3006');});