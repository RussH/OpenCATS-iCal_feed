# email2activty for OpenCATS  

##Overview
This is a simple configurable plugin for OpenCATS that will connect to a POP3 or IMAP account, parse it to see if the emails are addressed to a candidate or client - and if there's a match, it'll copy the body of the email to the activity record for the (Candidate or Client). 

It's intended to be used for outbound emails, but can be amended to monitor and work for inbound emails as well.

- (This isn't a recommended configuration, as you probably have allot of inbound emails with Clients/Candidates that you don't necessarily want copied into the opencats database)

##Usage
When sending emails that you want copied into the activity record, you need to cc the account you've defined as the 'opencats account'. For example please cc opencats@yourdomain.com

We recognise that some Candidates may also be Client contacts, therefore when sending outbound emails you can define a special character (by default it's a '#') - if this is detected in the subject line, it not match the candidate record, and will only consider the recipient to be a Client contact.

##Configuration
Please update your imap account details in config.php. Note if you have an old (CentOS 5 ) you may have to replace the connection string in imaper.class.php as recent PHP syntax has changed. Don't ask me which PHP versions are supported. PHP documentation for this sucks.

Note the directions on line 131 of imaper.class.php;
	            // ALTER TABLE `opencats`.`activity` ADD COLUMN `message_uid` INT(11) NULL AFTER `date_modified`;

- for this script to work you need to run that command to add a 'message_uid' field to the activity table.

##Author
Written by Magician on behalf of RussH

##Comments and Questions
Please post user questions in the OpenCATS forum. Issues can go into github issue tracking for the Opencats project. 