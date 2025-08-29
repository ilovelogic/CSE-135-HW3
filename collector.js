/**
 * COLLECTOR.JS
 */

let userAgent, userLang, acceptsCookies, allowsJavaScript;
let allowsImages, allowsCSS;
let userScreenWidth, userScreenHeight;
let userWindowWidth, userWindowHeight;
let userNetConnType;
let pageLoadTimingObject, pageLoadStart, pageLoadEnd, pageLoadTimeTotal;


let sessionID = localStorage.getItem('sessionID');
if (!sessionID) {
  sessionID = (window.crypto?.randomUUID?.())
    || (Date.now().toString() + Math.random().toString(36).substring(2));
  localStorage.setItem('sessionID', sessionID);
} else {
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

  // set the test image to a 1x1 pixel gif to fire events on the image
  testImg.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";

  // if the user allows CSS, checks if generated element is styled properly
  allowsCSS = false;
  const cssDiv = document.createElement('div');
  cssDiv.className = 'css-check';
  document.body.appendChild(cssDiv);
  const computedCSS = window.getComputedStyle(cssDiv).getPropertyValue('color');
  allowsCSS = (computedCSS === 'red');

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

  const timingObject = performance.getEntriesByType('navigation');

  if (timingObject) {
    pageLoadTimingObject = timingObject;
    pageLoadStart = timingObject.startTime;
    pageLoadEnd = pageLoadStart + pageLoadTimeTotal;
    pageLoadTimeTotal = timingObject.duration;
  } else {
    // use deprecated version if not supported
    pageLoadTimingObject = window.performance.timing;
    pageLoadStart = window.performance.timing.navigationStart;
    pageLoadEnd = window.performance.timing.loadEventEnd;
    pageLoadTimeTotal = pageLoadEnd - pageLoadStart;
  }
}

/**
 * ACTIVITY COLLECTION
 */

let activityLog = [];

// Helper function to log activity with session ID and timestamp
function logActivity(eventData) {
  activityLog.push({
    sessionID,
    timestamp: Date.now(),
    ...eventData
  });
}

// All thrown errors
window.addEventListener('error', (event) => {
  const { message, filename, lineno, colno, error } = event;
  logActivity({ type: 'error', message, filename, lineno, colno, error });
});

// Cursor positions
window.addEventListener('mousemove', (event) => {
  const { clientX, clientY } = event;
  // TODO: throttle this to avoid excessive logging
  logActivity({ type: 'mousemove', clientX, clientY });
});

// Clicks and which mouse button it was
window.addEventListener('click', (event) => {
  const { button } = event;
  logActivity({ type: 'click', button });
});

// Scrolling and coordinates of the scroll
window.addEventListener('scroll', () => {
  const { scrollX, scrollY } = window;
  logActivity({ type: 'scroll', scrollX, scrollY });
});

// Key down events
window.addEventListener('keydown', (event) => {
  const { key, code } = event;
  logActivity({ type: 'keydown', key, code });
});

// Key up events
window.addEventListener('keyup', (event) => {
  const { key, code } = event;
  logActivity({ type: 'keyup', key, code });
});

// for idle detection, see: https://developer.mozilla.org/en-US/docs/Web/API/Idle_Detection_API

// Any idle time where no activity happened for a period of 2 or more seconds

// Record when the break ended

// Record how long it lasted(in milliseconds)

// When the user entered the page
window.addEventListener('focus', () => {
  logActivity({ type: 'focus' });
});

// When the user left the page
window.addEventListener('blur', () => {
  logActivity({ type: 'blur' });
});

// Which page the user was on

/**
 * SENDING THE DATA
 */

// store in local storage to stay persisitent between page loads/disconnects

/**
 * Sends collected data to the server using the Fetch API.
 * Do not assume that every network request will work 100 % of the time.
 * Save the data locally, then make attempts to send updates to the server.
 * Fetch API: https://developer.mozilla.org/en/docs/Web/API/Fetch_API
 */

const response = await fetch("https://annekelley.site/api.php/post", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify({username: "example", password: "password" }),
});
