<?php
class iMaper {
    public function __construct() 
    {
        $this->connect();

        $mails = imap_search($this->mbox, 'ALL from guntram'); // Here we can define additonal seach IN-MAIL criterias, such as "UNSEEN from user@mail.com", or "SEEN from Tania" (Don`t ask me who Tania is - no idea.)
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
                $this->save_to_db($data);
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
        $this->mbox = imap_open("{".IMAP_HOST.":".IMAP_PORT."/".IMAP_PROTO."/novalidate-cert}", IMAP_USER, IMAP_PASS, OPERATOR) or die("Can not connect to: " . IMAP_HOST );
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
    
    private function save_to_db($data)
    {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME) or die("Error " . mysqli_error($link)); 

        foreach ($data as $d)
        {
            $sql = "SELECT `id` FROM `" . SAVE_TO_TABLE . "` WHERE `activity_id` = '" . $d['uid']. "'"; 
            $res = $conn->query($sql);
            while ($row = mysqli_fetch_assoc($res))
                    $id = $row['id'];

            if ($id)
                // Record exists -> UPDATE
                $sql = "UPDATE `" . SAVE_TO_TABLE . "` SET 
                                        `notes` = '".mysqli_real_escape_string($conn, $d['body'])."',
                                        `date_modified` = NOW()
                                        WHERE `id` = '".$id."'";        
            else
                // Record is new -> INSERT 
                $sql = "INSERT INTO " . SAVE_TO_TABLE . " 
                                        (`activity_id`, `date_created`, `notes`)
                                        VALUES
                                        ('".$d['uid']."', NOW(), '".mysqli_real_escape_string($conn, $d['body'])."')   
                                     ";
            $conn->query($sql);
            echo IMAP_USER . ": #" . $d['uid'] ."\n";
        }
              
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