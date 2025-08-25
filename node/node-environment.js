import express from 'express';
const app = express(); // 

app.get('/node/node-environment.js', (request, response) => {
    let headers_str = "";
    let env_vars_str = "";
    for (const [key, value] of Object.entries(request.headers)) {
        headers_str += `<li>${key}: ${value}</li>`;
    }

    // firefox detection
    const maybe_firefox = request.headers['user-agent'] || "";
    const uses_firefox = maybe_firefox.toLowerCase().includes('firefox');
    const firefox_mssg = uses_firefox ? "<h3><strong>Uses Firefox</strong></h3>" : "<h3><strong>No Firefox</strong></h3>";
    for (const [key, value] of Object.entries(process.env)) {
        env_vars_str += `<li>${key}: ${value}</li>`;
    }
    response.set("Cache-Control", "no-cache");
    response.set("Content-Type", "text/html");
    response.send("<!doctype html>"
        + "<head><title>Environment Variables</title></head>"
        + "<body><h1 align=center>Environment Variables</h1>"
        + "<h2>HTTP Request Headers</h2>"
        + `<ul>${headers_str}</ul>`
        + `${firefox_mssg}<br>`
        + "<h2>Environment Variables</h2>"
        + `<ul>${env_vars_str}</ul>`
        + "</body></html>");
});

app.listen(3002, () => {
    console.log('Server running on port 3002');}
);