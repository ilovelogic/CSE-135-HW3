/**
 * COLLECTOR.JS
 */

let sessionID = localStorage.getItem('sessionID');
if (!sessionID) {
  const sessionID = (window.crypto?.randomUUID?.())
    || (Date.now().toString() + Math.random().toString(36).substring(2));
  localStorage.setItem('sessionID', sessionID);
} else {
  /**
 * STATIC COLLECTION
 */

  // user agent string
  const userAgent = window.navigator.userAgent;

  // the user's language
  const userLang = window.navigator.language;

  // if the user accepts cookies
  const acceptsCookies = window.navigator.cookieEnabled;

  // if the user allows JavaScript
  let allowsJavaScript = true;

  // if the user allows images
  let allowsImages = false;
  const testImg = new Image();
  testImg.onload = function () {
    allowsImages = true;
  };

  // set the test image to a 1x1 pixel gif to fire events on the image
  testImg.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="; 

  // if the user allows CSS, checks if generated element is styled properly
  let allowsCSS = false;
  const cssDiv = document.createElement('div');
  cssDiv.className = 'css-check';
  document.body.appendChild(cssDiv);
  const computedCSS = window.getComputedStyle(cssDiv).getPropertyValue('color');
  allowsCSS = (computedCSS === 'red');

  // User's screen dimensions
  const [userScreenWidth, userScreenHeight] = [window.screen.width, window.screen.height];

  // User's window dimensions
  const [userWindowWidth, userWindowHeight] = [window.innerWidth, window.innerHeight];

  // User's network connection type
  const userNetConnType = window.navigator.connection.effectiveType;

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
    const pageLoadTimingObject = timingObject;
    const pageLoadStart = timingObject.startTime;
    const pageLoadEnd = pageLoadStart + pageLoadTimeTotal;
    const pageLoadTimeTotal = timingObject.duration;
  } else {
    // use deprecated version if not supported
    const pageLoadTimingObject = window.performance.timing;
    const pageLoadStart = window.performance.timing.navigationStart;
    const pageLoadEnd = window.performance.timing.loadEventEnd;
    const pageLoadTimeTotal = pageLoadEnd - pageLoadStart;
  }
}

/**
 * Then we want to start collecting all the dynamic data, associating/tagging it with the sessionID.
 * What about constantly changing data like the mouse movement and stuff?
 *  - Think the solution here is like he said in class, send all of it dirty, then we can worry about "packing" it later.
 * For sessionization we can either do crypto.randomUUID() or Date timenow and + random or something.
 * Then we store this session as a Cookie or LocalStorage or both
 */


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

