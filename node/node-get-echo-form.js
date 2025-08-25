import express from 'express'; // importing Express module
const app = express(); // new Express application

app.get('/node/node-get-echo-form.js', (request, response) => { // called when a get received at the url
    response.set("Content-Type", "text/html");
    response.set("Cache-Control", "no-cache");
    response.send("<!doctype html>"
        + "<head><title>Basic Form</title></head>"
        + "<body><h1 align=center>Enter details to test our GET echoing!</h1>"
        + "<form action=\"node-get-echo.js\" method=\"get\">Username: <input type=\"text\" name=\"username\"><br>"
        + "<input type = \"submit\" value = \"Send\"><br>"
        + "</form></body></html>");
});

app.listen(3003, () => {
    console.log('Server running on port 3003');
});