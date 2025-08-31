/** 
 * DISCLAIMER - While yes, it is dangerous to leave
 * an exposed DEBUG flag or even allow one at all, I argue that:
 * 1. We are not collecting any sensitive user information that
 *    users should not already be aware of.
 * 2. The collector.js file has to be sent to the client anyways,
 *    so anyone determined enough can find out what is being collected.
 * 3. This is a learning exercise, and we are not a company!
*/
const DEBUG = true;

if (DEBUG) {
  console.log("Debugging.");
}

/**
 * COLLECTOR.JS
 */

let userAgent, userLang, acceptsCookies, allowsJavaScript;
let allowsImages, allowsCSS;
let userScreenWidth, userScreenHeight;
let userWindowWidth, userWindowHeight;
let userNetConnType;
let pageLoadTimingObject, pageLoadStart, pageLoadEnd, pageLoadTimeTotal;

/*
If we later want to keep track of users (long-term) in addition to individual sessions, we can use:
let userID = localStorage.getItem('userID');
if (!userID) {
  userID = (window.crypto?.randomUUID?.())
    || (Date.now().toString() + Math.random().toString(36).substring(2));
  localStorage.setItem('userID', userID);
}
*/


let sessionID = sessionStorage.getItem('sessionID');
if (!sessionID) {
  sessionID = (window.crypto?.randomUUID?.())
    || (Date.now().toString() + Math.random().toString(36).substring(2));
  localStorage.setItem('sessionID', sessionID);

  if (DEBUG) {
    console.log("New session ID:", sessionID);
  }
}

/**
 * STATIC COLLECTION
 */
function collectStaticData() {
  userAgent = navigator.userAgent;
  userLang = navigator.language;
  acceptsCookies = navigator.cookieEnabled;
  allowsJavaScript = true;

  // Images
  allowsImages = false;
  const testImg = new Image();
  testImg.onload = () => { allowsImages = true; };
  testImg.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";

  // CSS
  document.addEventListener('DOMContentLoaded', function() {
    allowsCSS = false;
    const cssDiv = document.createElement('div');
    cssDiv.className = 'css-check';
    document.body.appendChild(cssDiv);
    const computedCSS = window.getComputedStyle(cssDiv).getPropertyValue('color');
    allowsCSS = (computedCSS === 'red');
  });

  // Dimensions
  userScreenWidth = window.screen.width;
  userScreenHeight = window.screen.height;
  userWindowWidth = window.innerWidth;
  userWindowHeight = window.innerHeight;

  // Network
  userNetConnType = navigator.connection?.effectiveType || "unknown";
}

/**
 * PERFORMANCE COLLECTION
 * 
 * Gathers the following performance-related information:
 * - The whole timing object 
 * - pageLoadStart always 0 or close to it
 * - pageLoadEnd in ms
 * - pageLoadTimeTotal in ms
 */
function collectPerformanceData() {
  const timingObject = performance.getEntriesByType("navigation")[0];
  const t = performance.timing;

  pageLoadTimingObject = timingObject || t

  if (timingObject) {
    pageLoadTimingObject = timingObject;

    // For modern browsers
    pageLoadStart = timingObject.startTime || 0; // usually 0
    pageLoadEnd = timingObject.loadEventEnd || timingObject.domComplete;
    pageLoadTimeTotal = (pageLoadEnd && pageLoadStart !== undefined)
      ? pageLoadEnd - pageLoadStart
      : timingObject.duration;
  } else {
    // Fallback for older browsers
    
    pageLoadTimingObject = t;
    pageLoadStart = t.navigationStart;
    pageLoadEnd = t.loadEventEnd;
    pageLoadTimeTotal = pageLoadEnd - pageLoadStart;
  }
}

/**
 * ACTIVITY COLLECTION
 */

let activityLog = [];
const MAX_LOG_ENTRIES = 20;
const FLUSH_INTERVAL = 20_000;
let eventCount = 0;

function persistLog() {
  localStorage.setItem("activityLog", JSON.stringify(activityLog));
}

// Helper function to log activity with session ID and timestamp
function logActivity(eventData) {
  eventCount += 1;
  activityLog.push({
    sessionID,
    eventTimestamp: Date.now(),
    ...eventData,
    eventCount
  });
  persistLog();

  if (activityLog.length >= MAX_LOG_ENTRIES) {
    flushActivityLog();
  }
}

// All thrown errors
window.addEventListener('error', (event) => {
  const { message, filename, lineno, colno, error } = event;
  logActivity({ eventType: 'error', message, filename, lineno, colno, error });
});

// Helper function to throttle events
function throttle(fn, limit) {
  let lastCall = 0;
  return function (...args) {
    const now = Date.now();
    if (now - lastCall >= limit) {
      lastCall = now;
      fn.apply(this, args);
    }
  };
}

// Cursor positions
window.addEventListener('mousemove', throttle((event) => {
  const { clientX, clientY } = event;
  logActivity({ eventType: 'mousemove', clientX, clientY });
}, 200)); // log at most every 200ms


// Clicks and which mouse button it was
window.addEventListener('click', (event) => {
  const { button } = event;
  logActivity({ eventType: 'click', button });
});

// Scrolling and coordinates of the scroll
window.addEventListener('scroll', () => {
  const { scrollX, scrollY } = window;
  logActivity({ eventType: 'scroll', scrollX, scrollY });
});

// Key down events
window.addEventListener('keydown', (event) => {
  const { key, code } = event;
  logActivity({ eventType: 'keydown', key, code });
});

// Key up events
window.addEventListener('keyup', (event) => {
  const { key, code } = event;
  logActivity({ eventType: 'keyup', key, code });
});

// Idle detection
async function setupIdleDetection() {
  // Check if the browser supports IdleDetector
  if ("IdleDetector" in window) {
    try {
      const permission = await IdleDetector.requestPermission();
      if (permission !== "granted") {
        console.warn("Idle Detection permission not granted, falling back.");
        return setupFallbackIdleDetection();
      }

      const idleDetector = new IdleDetector();

      idleDetector.addEventListener("change", () => {
        const userState = idleDetector.userState;     // "active" / "idle"
        const screenState = idleDetector.screenState; // "locked" / "unlocked"

        logActivity({
          eventType: "idle-detection",
          userState,
          screenState,
          eventTimestamp: Date.now()
        });
      });

      await idleDetector.start({
        threshold: 2000 // idle if no activity for 2s
      });

      console.log("✅ IdleDetector started!");
      return;
    } catch (err) {
      console.error("Idle Detection API failed:", err);
      // fallback if something goes wrong
      return setupFallbackIdleDetection();
    }
  }

  // When not supported then fallback
  setupFallbackIdleDetection();
}

function setupFallbackIdleDetection() {
  let lastActive = Date.now();
  let idle = false;

  function resetIdle() {
    if (idle) {
      // User just returned from idle
      const now = Date.now();
      logActivity({
        eventType: "idle-return",
        idleDuration: now - lastActive,
        eventTimestamp: now
      });
      idle = false;
    }
    lastActive = Date.now();
  }

  // Listen for user activity
  ["mousemove", "keydown", "scroll", "click"].forEach(event => {
    window.addEventListener(event, resetIdle);
  });

  // Check periodically if user is idle
  setInterval(() => {
    const now = Date.now();
    if (!idle && now - lastActive >= 2000) {
      idle = true;
      logActivity({
        eventType: "idle-start",
        eventTimestamp: now
      });
    }
  }, 1000);
}

// When the user entered the page
window.addEventListener('focus', () => {
  logActivity({ eventType: 'focus' });
});

// When the user left the page
window.addEventListener('blur', () => {
  logActivity({ eventType: 'blur' });
});

// Which page the user was on
function trackPage() {
  logActivity({
    eventType: "page-view",
    url: window.location.href,
    title: document.title
  });
}

/**
 * SENDING THE DATA
 */

setInterval(() => {
  flushActivityLog();
}, FLUSH_INTERVAL);

function sendStaticData() {
  collectStaticData();

  const url = "/api.php/static";
  const data = JSON.stringify({
    userAgent,
    userLang,
    acceptsCookies,
    allowsJavaScript,
    allowsImages,
    allowsCSS,
    userScreenWidth,
    userScreenHeight,
    userWindowWidth,
    userWindowHeight,
    userNetConnType,
    id: sessionID
  });
  navigator.sendBeacon(url, new Blob([data], { type: "application/json" }));

  if (DEBUG) {
    console.log("Sent static data:", data);
  }
}

function sendPerformanceData() {
  collectPerformanceData();
  const url = "/api.php/performance";
  const data = JSON.stringify({
    pageLoadTimingObject,
    pageLoadStart,
    pageLoadEnd,
    pageLoadTimeTotal,
    id: sessionID
  });
  navigator.sendBeacon(url, new Blob([data], { type: "application/json" }));

  if (DEBUG) {
    console.log("Sent performance data:", data);
  }
}

async function flushActivityLog() {
  if (DEBUG) {
    console.log("Flushing activity log:", activityLog);
  }

  if (activityLog.length === 0) return;

  const url = "/api.php/activity";
  const data = JSON.stringify({ activityLog });
  const blob = new Blob([data], { type: "application/json" });

  const sent = navigator.sendBeacon(url, blob);

  if (sent) {
    activityLog = [];
    persistLog();
  } else {
    console.error("sendBeacon failed! Retaining logs.");
  }
}

window.addEventListener('load', () => {
  sendPerformanceData();
});

sendStaticData();
setupIdleDetection();
trackPage();

// Strictly for testing purposes, REMOVE LATER
function getData(resource, colNames) {
  const url = "/api.php";

  if (resource == "static") {
    url += "/static";
  }
  else if (resource == "performance") {
    url += "/performance";
  }
  else if (resource == "activity") {
    url += "/activity";
  }
  else {
    // send an error message
  }

  // path will be /api.php/{resource}/{col_1}&{col_2}&...&{col_n}
  if (colNames.length != 0) {
    url += "/";
    let lastIndex = colNames.length - 1;
    for (let i = 0; i < lastIndex; i++) {
      url += colNames[i] + "&"; // & purely for purposes of debugging, php api would work fine without it
      // since we are using strpos and no colName is a subset of another colName
    }
    url += colNames[lastIndex];
  }

  try {
    const response = fetch(url);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }

    const result = response.json();
    console.log(result);
    return result;
  } catch (error) {
    console.error(error.message);
  }
}

result = getData("static", ["userScreenWidth", "userScreenHeight"]);
console.error(result);