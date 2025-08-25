

import express from 'express'; // importing Express module
const app = express(); // new Express application

app.get('/node/node-hello-html-world.js', (request, response) => { // called when a get received at the url
    response.set("Content-Type", "text/html");
    response.set("Cache-Control", "no-cache");
    
    response.send("<!doctype html>"
        + "<head><title>Hello HTML World</title></head>"
        + "<body><h1 align=center>Hello HTML World!</h1></body>"
        + "</html>");
});

app.listen(3010, () => {
    console.log('Server running on port 3010');}); // gets server up and listening on post 3010