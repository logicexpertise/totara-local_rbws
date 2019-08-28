# Totara Report builder web service plugin
## Summary
This plugin provides a web service function __local\_rbws\_get\_report_by\_id__ for exporting Totara report builder reports, e.g. for ingesting into other systems such as enterprise business reporting applications.

## Installation
Download and extract into the __local/rbws__ directory of your Totara instance, and follow usual Totara plugin installation steps.

## Usage
- Add external web service to your Totara, and add the plugin's web service function (__local\_rbws\_get\_report\_by\_id__) to the web service.

- Call the web service from the relevant external application, passing the __id__ parameter. For example:

  curl "https://your.totara.url/totara_pivottable/webservice/rest/server.php?wstoken=abcdefghij123456ABC98765&wsfunction=local_rbws_get_report_by_id&moodlewsrestformat=json&id=999"

The function returns the following information: 

- __info__ (information about the report), with the following fields: fullname, shortname, description, source
- __rows__ (the data/records contained in the report). All fields defined in the report as being exportable are returned, and vary from report to report
- __warnings__ (information about the content of the returned data, or errors if any).
