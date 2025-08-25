/* document.addEventListener("visibilitychange", () => {
  if (document.visibilityState === "hidden") {
    navigator.sendBeacon("/log", analyticsData);
  }
}); // Navigator: sendBeacon() method */


// IIFE creates an isolated scope
// prevents polluting the global namespace and protects internal variables and functions
(() => {
  // config constants for the collector
  const LOG_ENDPOINT = "/log";               // URL endpoint where collected data will be sent
  const BATCH_MAX_SIZE = 10;                 // max number of events to batch before sending
  const BATCH_SEND_INTERVAL = 5000;          // max time (ms) before batch is sent even if not full

  // Internal state for managing event collection
  let eventQueue = [];                       // Array to hold queued events before sending
  let sendTimeoutId = null;                  // Timeout ID to manage delayed batch sending

  // Function to send data to the server using the most reliable method available
  function sendData(data) {
    // Convert event data array to JSON string for transmission
    const payload = JSON.stringify(data);
   
    // If the browser supports navigator.sendBeacon (designed for async sending during unload)
    if (navigator.sendBeacon) {
      // Prepare a Blob object with the JSON payload and correct mime type (application/json)
      const blob = new Blob([payload], { type: "application/json" });
      // Blob = Binary large object, i.e. immutable raw data
     
      // Attempt to send the data asynchronously, reliably even if page unloads immediately after
      // Returns boolean indicating whether send was initiated
      const sent = navigator.sendBeacon(LOG_ENDPOINT, blob);
     
      // If sendBeacon fails (which is rare), fallback to using fetch
      if (!sent) fallbackFetch(payload);
    } else {
      // Browser does not support sendBeacon, fallback to fetch API with keepalive flag to send data
      fallbackFetch(payload);
    }
  }
 
  // Fallback sending method using fetch API for asynchronous POST requests
  // The keepalive flag ensures the request completes even if the page unloads
  // Errors are caught and logged but do not block execution
  function fallbackFetch(payload) {
    fetch(LOG_ENDPOINT, {
      method: "POST",                     // Use POST method to submit data
      headers: { "Content-Type": "application/json" }, // Inform server that data is JSON
      body: payload,                     // Send the JSON payload as the request body
      keepalive: true                   // Allow request to outlive page unload
    }).catch(err => {
      // Optional: log errors to the console, can be extended to retries or error reporting
      console.error("Collector fetch error:", err);
    });
  }

  // Sends all queued events immediately and resets timeout state
  function flushEvents() {
    // If no events to send, do nothing
    if (eventQueue.length === 0) return;

    // Send all collected events in one batch
    sendData(eventQueue);

    // Clear the queue after sending to avoid duplicate sends
    eventQueue = [];

    // Clear any existing scheduled timeout since we're sending events now
    clearTimeout(sendTimeoutId);
    sendTimeoutId = null;
  }

  // Schedule sending currently queued events after a configured delay
  // (if no send is currently scheduled)
  function scheduleFlush() {
    if (sendTimeoutId === null) {
      sendTimeoutId = setTimeout(flushEvents, BATCH_SEND_INTERVAL); // did not us an () => here
      // since flushEvents does not need access to this context
    }
  }

  // Adds a new event to the queue with uniform metadata and event-specific data
  function collectEvent(eventType, eventData = {}) {
    const event = {
      type: eventType,                          // Type or name of the event for identification
      timestamp: new Date().toISOString(),     // ISO timestamp when event was collected
      url: window.location.href,                // Current page URL for context
      referrer: document.referrer || null,      // Referring URL if any, or null if none
      userAgent: navigator.userAgent,           // Browser user agent string for environment info
      ...eventData                              // Spread additional event-specific details passed in
    };

    // Add the newly created event object to the queue
    eventQueue.push(event);

    // If queue reached max batch size, flush immediately, else schedule a delayed flush
    if (eventQueue.length >= BATCH_MAX_SIZE) {
      flushEvents();
    } else {
      scheduleFlush();
    }
  }

  // Expose the collectEvent function to the global window object so other scripts can send data
  window.collectEvent = collectEvent;

  // Automatically collect page load event with additional details about user screen and language
  collectEvent("page_load", {
    screenWidth: window.screen.width,        // Width of user’s screen in pixels
    screenHeight: window.screen.height,       // Height of user’s screen in pixels
    language: navigator.language              // Browser language setting for localization info
  });

  // Listen for clicks on elements with `data-track-event` attribute to capture user interaction
  document.addEventListener("click", (evt) => {
    // Use closest to select the nearest ancestor (or itself) with tracking attribute
    const target = evt.target.closest("[data-track-event]");
   
    // If no such element was clicked, do not track
    if (!target) return;

    // Collect informative details about the clicked element and the custom tracking data specified
    const eventData = {
      elementTag: target.tagName,                  // Tag name of clicked element (e.g., BUTTON, A)
      elementClasses: target.className || null,    // CSS classes applied to the element
      elementId: target.id || null,                 // Element’s ID attribute if present
      customData: target.getAttribute("data-track-event") // Custom string describing the event
    };

    // Send a click event with gathered contextual data
    collectEvent("click", eventData);
  });
 
  // Register visibility change event listener using the rigorous pattern from the given snippet
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      // Use the performant sendBeacon API to asynchronously send any remaining analytics data
      flushEvents();
    }
  });

  // attempt to flush events when the user unloads the page as backup
  window.addEventListener("beforeunload", flushEvents);

})();