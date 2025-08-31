/* Given the parameters: 
  - resource (string): "static", "performance", or "activity"
  - colNames (array[string]): includes requested columns
Creates a data request from api.php of the form:
  api.php/{resource}/{col_1}&{col_2}&...&{col_n}
where resource specifies the table name and each col_i is a requested column of that table.
Returns JSON encoding of response
 !--- Need to get the JSON encoding into the right format for ZingChart ---!
*/

async function getData(resource, colNames) {
  let url = "/api.php";

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
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }

    const result = await response.json();
    console.log(result);
    return result;
  } catch (error) {
    console.error(error.message);
  }
}