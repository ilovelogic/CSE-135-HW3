/* Given the parameters: 
  - resource (string): "static", "performance", or "activity"
  - colNames (array[string]): includes requested columns
Creates a data request from api.php of the form:
  api.php/{resource}/{col_1}&{col_2}&...&{col_n}
where resource specifies the table name and each col_i is a requested column of that table.
Returns JSON encoding of response
 !--- Need to get the JSON encoding into the right format for ZingChart ---!
*/
export async function getData(resource, colNames) {
  const cols = colNames.join("&");
  // Build the path style URL, not a query string param
  const url = `/endpoint.php/${encodeURIComponent(resource)}/${encodeURIComponent(cols)}`;
  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const result = await response.json();
    console.log("Proxy response:", result);
    return result;
  } catch (err) {
    console.error("Proxy fetch failed:", err);
    return null;
  }
}

export async function userAgentsToWordCloud() {
  try {
    const data = await getData("static", ["userAgent"]); // already array of objects

    return data
      .map(item => {
        const ua = item.userAgent;
        let parts = [];

        // --- Browser + version ---
        if (/Chrome\/(\d+)/.test(ua) && !/Edg\//.test(ua)) {
          const version = ua.match(/Chrome\/(\d+)/)[1];
          parts.push(`Chrome_${version}`);
        } else if (/Version\/(\d+).+Safari\//.test(ua) && !/Chrome\//.test(ua)) {
          const version = ua.match(/Version\/(\d+)/)[1];
          parts.push(`Safari_${version}`);
        } else if (/Firefox\/(\d+)/.test(ua)) {
          const version = ua.match(/Firefox\/(\d+)/)[1];
          parts.push(`Firefox_${version}`);
        } else if (/Edg\/(\d+)/.test(ua)) {
          const version = ua.match(/Edg\/(\d+)/)[1];
          parts.push(`Edge_${version}`);
        } else {
          parts.push("Other");
        }

        // --- OS ---
        if (/Windows NT 10/.test(ua)) {
          parts.push("Windows_10");
        } else if (/Windows NT 11/.test(ua)) {
          parts.push("Windows_11");
        } else if (/Mac OS X 10_15/.test(ua)) {
          parts.push("MacOS_10_15");
        } else if (/Mac OS X 11/.test(ua)) {
          parts.push("MacOS_11");
        } else if (/Linux/.test(ua)) {
          parts.push("Linux");
        }

        return parts.join(" ");
      })
      .join(" "); // big string for ZingChart
  } catch (err) {
    console.error("Error processing user agents:", err);
    return "";
  }
}

/**
 * @param {string} colName - column name to count values for
 * @returns {Promise<Object>} an object with key-value pairs of unique values and their counts
 * Example: { "es": 3, "en-US": 2 }
 */
export async function countKeyValue(colName) {
  const data = await getData("static", [colName]);

  return data.reduce((acc, item) => {
    const index = item[colName];  // <-- dynamic lookup
    acc[index] = (acc[index] || 0) + 1;
    return acc;
  }, {});
}