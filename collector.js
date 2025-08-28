/**
 * You should be able to tie this data to a specific user session.
 */

/**
 * Steps to refactor this process to be DRY and efficient:
 * We only want to send static data one time and be done with it for a new session.
 * This means on this scripts run (every time the user loads this script for the first time, we want to):
 *  - Find a currently existing sessionID from localstorage
 *  - If it exists, use it; if not, create a new one
 *  - Collect static data
 * 
 * Then we want to start collecting all the dynamic data, associating/tagging it with the sessionID.
 * 
 * What about constantly changing data like the mouse movement and stuff?
 *  - Think the solution here is like he said in class, send all of it dirty, then we can worry about "packing" it later.
 * For sessionization we can either do crypto.randomUUID() or Date timenow and + random or something.
 * Then we store this session as a Cookie or LocalStorage or both
 */

/**
 * STATIC COLLECTION
 */

// user agent string
userAgent = window.navigator.userAgent;

// the user's language
userLang = window.navigator.language;

// if the user accepts cookies
acceptsCookies = window.navigator.cookieEnabled;

// if the user allows JavaScript
allowsJavaScript = true;

// if the user allows images
allowsImages = false;
const testImg = new Image();
testImg.onload = function () {
  allowsImages = true;
};

testImg.onerror = function () {
  allowsImages = false;
};

// if the user allows CSS
allowsCSS = false;
const cssDiv = document.createElement('div');
cssDiv.className = 'css-check';
cssDiv.style.color = 'black';
document.body.appendChild(cssDiv);
const computedCSS = window.getComputedStyle(cssDiv).getPropertyValue('color');
allowsCSS = (computedCSS === 'black');

// User's screen dimensions
userScreenWidth, userScreenHeight = window.screen.width, window.screen.height;

// User's window dimensions
userWindowWidth, userWindowHeight = window.innerWidth, window.innerHeight;

// User's network connection type
userNetConnType = window.navigator.connection.effectiveType;

/**
 * PERFORMANCE COLLECTION
 * 
 * Gathers the following performance-related information:
 * - The whole timing object
 * - Specifically when the page started loading
 * - Specifically when the page ended loading
 * - The total load time
 */

const [timingObject] = performance.getEntriesByType('navigation');

if (timingObject) {
  pageLoadTimingObject = timingObject;
  pageLoadTimeTotal = timingObject.duration;
  pageLoadStart = timingObject.startTime;
  pageLoadEnd = pageLoadStart + pageLoadTimeTotal;
} else {
  // use deprecated version if not supported
  pageLoadTimingObject = window.performance.timing;
  pageLoadStart = window.performance.timing.navigationStart;
  pageLoadEnd = window.performance.timing.loadEventEnd;
  pageLoadTimeTotal = pageLoadEnd - pageLoadStart;
}

/**
 * ACTIVITY COLLECTION
 */

let activityLog = [];

// All thrown errors
window.addEventListener('error', (event) => {
  const { message, filename, lineno, colno, error } = event;
  activityLog.push({
    type: 'error',
    message,
    filename,
    lineno,
    colno,
    error
  });
});

// Cursor positions
window.addEventListener('mousemove', (event) => {
  const { clientX, clientY } = event;
  activityLog.push({
    type: 'mousemove',
    clientX,
    clientY
  });
});

// Clicks and which mouse button it was
window.addEventListener('click', (event) => {
  const { button } = event;
  activityLog.push({
    type: 'click',
    button
  });
});

// Scrolling and coordinates of the scroll
window.addEventListener('scroll', (event) => {
  const { scrollX, scrollY } = window;
  activityLog.push({
    type: 'scroll',
    scrollX,
    scrollY
  });
});

// Key down events
window.addEventListener('keydown', (event) => {
  const { key, code } = event;
  activityLog.push({
    type: 'keydown',
    key,
    code
  });
});

// Key up events
window.addEventListener('keyup', (event) => {
  const { key, code } = event;
  activityLog.push({
    type: 'keyup',
    key,
    code
  });
});

// for idle detection, see: https://developer.mozilla.org/en-US/docs/Web/API/Idle_Detection_API

// Any idle time where no activity happened for a period of 2 or more seconds

// Record when the break ended

// Record how long it lasted(in milliseconds)

// When the user entered the page
window.addEventListener('focus', (event) => {
  activityLog.push({
    type: 'focus',
    timestamp: Date.now()
  });
});

// When the user left the page
window.addEventListener('blur', (event) => {
  activityLog.push({
    type: 'blur',
    timestamp: Date.now()
  });
});

// Which page the user was on

/**
 * SENDING THE DATA
 *
 * Sends collected data to the server using the Fetch API.
 * Do not assume that every network request will work 100 % of the time.
 * Save the data locally, then make attempts to send updates to the server.
 * Fetch API: https://developer.mozilla.org/en/docs/Web/API/Fetch_API
 */

