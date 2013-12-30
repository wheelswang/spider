<?php
// The path to the FTP file, including login arguments
$ftp_path = 'ftp://u798791054:letmego555@eccbuy.net/public_html/index.php';

// Allows overwriting of existing files on the remote FTP server
$stream_options = array('ftp' => array('proxy' => 'tcp://proxy.tencent.com:8080/'));

// Creates a stream context resource with the defined options
$stream_context = stream_context_create($stream_options);

// Opens the file for writing and truncates it to zero length 
if ($fh = fopen($ftp_path, 'r', 0, $stream_context))
{
    // Writes contents to the file
	while(!feof($fh)) {
		$line = fgets($fh);
    
		echo $line;
	}
    // Closes the file handle
    fclose($fh);
}
else
{
    die('Could not open file.');
}
?>
