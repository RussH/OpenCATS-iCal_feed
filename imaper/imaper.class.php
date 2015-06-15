<?php
class iMaper {
    public function __construct() 
    {
        $this->connect(); // Connect MAIL
        $this->conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die("Error " . mysqli_error($link));  // Connect DB
        
        $emails = $this->get_emails(); // Extract all the emails, that we will check for in the email
            
        $this->ca = $emails['ca'];
        $this->co = $emails['co'];
                    

        foreach ($this->ca as $email=>$id)
            $this->main($email);
        
        foreach ($this->co as $email=>$id)
            $this->main($email);        

    }
    
    private function main($mail)
    {
    //	The disabled line below is if you want to search on all inbound emails rather than outbound 
    //        $mails = imap_search($this->mbox, 'ALL from '.$mail); // Here we can define additonal seach IN-MAIL criterias, such as "UNSEEN from user@mail.com", or "SEEN from Tania" (Don`t ask me who Tania is - no idea.)
    //	The line below searches on all outbound emails.  
	      $mails = imap_search($this->mbox, 'ALL to '.$mail);
        if ($mails)
        {
            rsort($mails);
        
            foreach ( $mails as $h)
            {
                $details = $this->get_message_details($h);
                $data[] = $details;
            }
            // Save all collected data into DB
            if (count($data))
                $this->save_to_db($data, $mail);
        }
        
    }


    private function connect()
    {
        // Chose protocol for connection and setup the port (configurable from config.php -> IMAP_POP3)
        // IMAP
        if (defined("IMAP_POP3") AND IMAP_POP3 == "IMAP" )
        {
            define("IMAP_PORT", 143);
            define("IMAP_PROTO", "imap");
            define("OPERATOR", "OP_READONLY");
        }
        // POP3
        else
        {
            define("IMAP_PORT", 110);
            define("IMAP_PROTO", "pop3");
            define("OPERATOR", "");
        }
        // Connect to the mail server
	// The disabled option is to parse on incoming emails. 
	// Note for some mail servers you will need to check the // 'Sent Mail' folder rather than 'Sent'
        //        $this->mbox = imap_open("{".IMAP_HOST.":".IMAP_PORT."/".IMAP_PROTO."/novalidate-cert}", IMAP_USER, IMAP_PASS, OPERATOR) or die("Can not connect to: " . IMAP_HOST );
		  $this->mbox = imap_open("{".IMAP_HOST.":".IMAP_PORT."/".IMAP_PROTO."/novalidate-cert}Sent", IMAP_USER, IMAP_PASS, OPERATOR) or die("Can not connect to: " . IMAP_HOST );
      }
    
    private function get_message_details($message_id)
    {
        mb_internal_encoding('UTF-8');

        // Get message details and raw body
        $details    = imap_fetch_overview($this->mbox, $message_id, 0);
        $struct     = imap_fetchstructure($this->mbox, $message_id); 

        // Pack some usefull data - could be needed or used later. 
        $message["type"]        = $struct->type;
        $message["encoding"]    = $struct->encoding;
        $message["subtype"]     = $struct->subtype;
        $message["id"]          = $message_id;
        $message["uid"]         = $details[0]->uid;
        
         // From decode for non ASCII symbols (ie. UTF-8)
        $f = imap_mime_header_decode($details[0]->from);
        $message["from"] = $f[0]->text;
        
        // Subject - UTF-8 Conversion
        $message["subject"] =  mb_decode_mimeheader($details[0]->subject);       
        
        // Work out the body!
        $body       = imap_fetchbody($this->mbox, $message_id, $struct->type);

        // Perform various decodings on body according to encoding type
        $body = $this->encodeMessage($body, $struct->encoding);
        $body = quoted_printable_decode($body);
        
        // Cut text as long, as set in config
        $body       = substr( $body, 0, MESSAGE_HOW_LONG);    
        $body = strip_tags($body);       
        $message["body"]    = $body;
       

        return $message;
    }
    
    private function save_to_db($data, $mail)
    {

        foreach ($data as $d)
        {

            // 4a. If there's a special character in the subject line 
            // (for example a '#' - configurable) then don't match the candidate record - 
            // update the client activity record instead.     
            if ( stripos(MAGIC_SYMBOL, $d['subject']) ) 
            {
                    $in_select = "contact";
                    $in_func = "contact_id";  
            } 
            else
            {
                    $in_select = "candidate";
                    $in_func = "candidate_id";  
            }    
            
            $internal_select = "SELECT `$in_func` as dataitem_id FROM `$in_select` WHERE `email1` = '".$mail."' OR `email2` = '".$mail."'";
            $res = $this->conn->query($internal_select);
            while ($row = mysqli_fetch_assoc($res))
                    $dataitem_id = $row['dataitem_id'];
            
            // ALTER TABLE `opencats`.`activity` ADD COLUMN `message_uid` INT(11) NULL AFTER `date_modified`;
            $sql = "SELECT `activity_id` FROM `" . SAVE_TO_TABLE . "` WHERE `message_uid` = '" . $d['uid']. "'";
            $res = $this->conn->query($sql);
            while ($row = mysqli_fetch_assoc($res))
                $id = $row['activity_id'];
            if ($id)
                // Record exists -> UPDATE
                $sql = "UPDATE `" . SAVE_TO_TABLE . "` SET 
                                        `notes` = '".mysqli_real_escape_string($this->conn, $d['body'])."',
                                        `data_item_id` = '" . $dataitem_id . "', 
                                        `date_modified` = NOW()
                                        WHERE `activity_id` = '".$id."'";        

            else
                // Record is new -> INSERT 
                $sql = "INSERT INTO " . SAVE_TO_TABLE . " 
                                        (`message_uid`, `date_created`, `notes`, `data_item_id`)
                                        VALUES
                                        ('".$d['uid']."', NOW(), '".mysqli_real_escape_string($this->conn, $d['body'])."', '".$dataitem_id."')   
                                     ";
            $this->conn->query($sql);
            echo IMAP_USER . ": #" . $d['uid'] ."\n";
        }
              
    }
    
    private function get_emails()
    {
        $sql = "SELECT `contact_id`,`email1`,`email2` FROM `contact`"; 
            $res = $this->conn->query($sql);
            while ($row = mysqli_fetch_assoc($res))
            {
                    $co_mail[$row['email1']] = $row['contact_id'];
                    $co_mail[$row['email2']] = $row['contact_id'];
            }
            
        $sql = "SELECT `candidate_id`, `email1` FROM `candidate` WHERE `is_active` ='1'"; 
            $res = $this->conn->query($sql);
            while ($row = mysqli_fetch_assoc($res))
                    $ca_mail[$row['email1']] = $row['candidate_id'];

        
        return array("co"=>$co_mail, "ca"=>$ca_mail);
    }
    
    private function encodeMessage($msg, $type)
    {
        mb_internal_encoding('UTF-8');

            if($type == 0){
                return mb_convert_encoding($msg, "UTF-8", "auto");
            } elseif($type == 1 OR stripos("Content-Transfer-Encoding: 8bit", $msg)){
                return imap_8bit($msg); //imap_utf8
            } elseif($type == 2 OR stripos("Content-Transfer-Encoding: base64", $msg)){
                return imap_base64(imap_binary($msg));
            } elseif($type == 3){
                return imap_base64($msg);
            } elseif($type == 4){
                return imap_qprint($msg);
                //return quoted_printable_decode($msg);
            } else {
                return $msg;
            }
    }
    
}
?>
