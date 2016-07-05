<?php

$file = "";
$dbug = "";
$sent = "";

if ( isset($_GET['pdf']) )
{
	$pdf = strip_tags($_GET['pdf']);
	
	if ( preg_match("!\.pdf$!i", $pdf) )
	{
		$sent = substr($pdf, 3);
		$file = substr( strrchr($sent, "/"), 1 );
				
		//if ( ($fsize = @filesize($_SERVER['DOCUMENT_ROOT'] . $sent)) !== false )
		if ( ($fsize = @filesize($sent)) !== false )
		{
			$cookiename = 'ssapdfDownload';
			setcookie($cookiename, "true", 0, '/', '', '', false);
			header('Accept-Ranges: bytes');  // download resume
			header('Content-Disposition: attachment; filename=' . $file);
			header('Content-Type: application/pdf');
			header('Content-Length: ' . $fsize);
			
			//readfile($_SERVER['DOCUMENT_ROOT'] . $sent);
			readfile($sent);
					
			$dbug .= "#read failed"; //if past readfile() then something went wrong
		}
		else
		{
			$dbug .= "#no file";
		}
	}
	else
	{
		$dbug .= "#not a pdf";
	}
}
else
{
	$dbug .= "#no get param";
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Download PDF</title>
	</head>
	<body>
			
	</body>
</html>