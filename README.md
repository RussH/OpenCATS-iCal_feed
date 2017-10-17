# ICal feed for OpenCATS  

##Overview
This is a simple standalone app to conenct to the opencats databse, and generate an iCal feed for public appointments. 

##Usage
<ADD>

##Configuration
Please update your db info in config.php. 

##Author
RussH

##Comments and Questions
Please post user questions in the OpenCATS forum. Issues can go into the issue list for this github repo 

##info
Here is some info on some of the fields:

PRODID:-//Thomas Multimedia//Clinic Mate//EN\n”;
The PRODID are your app / company details in the format Business Name//Product Name//Language.

BEGIN:VEVENT
The start tag for an event. You can have as many vevents as you require.

SUMMARY
This is the event title.

UID
A unique ID for the event. This is important and required, and allows you to push changes to event details after they have been created. If you are retrieving database rows, the primary key is an ideal candidate for the UID value.

STATUS (optional. default value is CONFIRMED)
The event status is optional and can be one of CONFIRMED, TENTATIVE, CANCELLED. A cancelled event shows with a line through text decoration on iOS.

DTSTART, DTEND
The event start and end timestamp. This should be formatted as demonstrated using the defined iCal format.If your dates are not already in the UTC timezone, you should convert them to UTC before outputting as this is the expected timezone when the timestamp ends with “Z”. There are options to specify a timezone which are beyond the scope of this tutorial.

LAST-MODIFIED (optional)
If your event has been modified you might like to set the last modified date in the same format as DTSTART above.

LOCATION (optional)
The title of the event location. There is also a GEO field where you can specify LAT/LONG coordinates, as well as a proposed VVENUE field to contain additional location data, however at least on the iPhone / iPad these tags are unused by the OS. Further reading here. I would expect better map integration in the near future for these fields.

It is worth noting that if you enter an address or phone number into the DESCRIPTION field (which I haven’t documented) in plain text, iOS will auto-link the text and will jump to the Maps app or Phone app with the details pre-filled.

END:VEVENT
The close tag for an event. Remember, you can have as many vevents as you require.

Once your web application is generating calendar data, its time to test!

I did my testing on my iPad. To add a calendar URL, go to: Settings > Mail, Contacts, Calendars, Add Account… Select Other > Add Subscribed Calendar, and then enter your URL in the Server field. Your calendar does not have to have the .ics extension. Then in the Calendar app, you can open the Calendars option and select only your new calendar for testing purposes. The refresh button is your friend! The Apple Calendar updates on its own schedule, which I think is somewhere around every 10-15 minutes.

The major thing to watch out for is formatting errors. iOS devices flatly refused all calendar data if there were any issues with new lines or anything else in contrast to the iCal specifications.



##source
Based upon https://stevethomas.com.au/php/how-to-build-an-ical-calendar-with-php-and-mysql.html
