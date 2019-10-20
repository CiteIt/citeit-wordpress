<?php

$JSON_FOLDER = "CiteIt.net_json/";

function get_json_from_webservice($submitted_url){
  	$CITEIT_BASE_URL = 'http://api.citeit.net/citeit?url=';
	$DOMAIN_FILTER_DISABLED = True; 
	$DOMAIN_FILTER = "citeit.net";  // do not save unless from this domain	
	$parse = parse_url($submitted_url);
	$submitted_url_domain = $parse['host'];
  	$data = array();

	// Is this a valid url?  If so save JSON results to file.
	if (filter_var($submitted_url, FILTER_VALIDATE_URL)) { 			

		// Only allow requests for this domain
		if (($submitted_url_domain == $DOMAIN_FILTER) | $DOMAIN_FILTER_DISABLED){
			$webservice_url = $CITEIT_BASE_URL . $submitted_url;
			
			// Call Webservice and return json
			$data = json_decode(file_get_contents($webservice_url), true);
			
			// Write json data to file
			foreach($data as $sha256=>$quote){
				$folder = "CiteIt.net_json/";
				$filename = $GLOBALS['JSON_FOLDER'] . $sha256 . '.json';
				$json = json_encode($data);
				file_put_contents($filename, $json);

				$public_url = sha_to_url($sha256);
				print("<a href='" . $public_url ."'>" . $sha256 . "</a> : " . $quote . "<br />");
			}
		}
 
	} else {
	    print("<p class='error'>$url is not a valid URL</p>");
	}
	return data;
}

function sha_to_url($sha256){
  // Construct a link to the JSON snippet on the main CiteIt site	
  //$CiteIt_read_base_url = 'https://read.citeit.net/quote/sha256/0.3/';
  // $shard = 	substr($sha256, 2);
  return  $GLOBALS['JSON_FOLDER'] . $sha256 . '.json';
}

function print_json_files($path){
	$files = array_diff(
				scandir($path),
				array('.', '..') // remove dots from array
			);
	print("<h3>Citation Snippets list:</h3>");
	print("<ul>");
	foreach($files as $file){
		print("<li><a href='$path$file'>$file</a></li>");
	}
	print("</ul>");
}
?>
<!DOCTYPE html>
<html lang="en-US" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#">
<head >
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CiteIt Examples: Call CiteIt.net Webservice from Php</title>
  <style>
	body {
		margin-top: 50px;
		margin-bottom: 70px;
		margin-right: 50px;
		margin-left: 50px;
		font-size: 125%;
	} 
	input {
		font-size: 125%;
	}
	input.submit {
		display: inline-block;
		padding: 15px 25px;
		font-size: 24px;
		cursor: pointer;
		text-align: center;
		text-decoration: none;
		outline: none;
		color: #fff;
		background-color: #444;
		border: none;
		border-radius: 15px;
		box-shadow: 0 9px #999;
	}
	input.submit:hover {
		background-color: #666
	}
	input.submit:active {
		background-color: #3e8e41;
		box-shadow: 0 5px #666;
		transform: translateY(4px);
	}
	input.url {
		width: 70%;
	}
	p.error {
		color: red;
		font-weight: 800;
	}
	div#list_citations {
		margin-top: 200px;
	}
	div#footer {
		margin-top: 70px;
		background-color: #ddd;
		border: 1px solid #bbb;
		padding: 30px 50px;
	}
	div#navigation {
		margin-top: 60px;
	}
	
  </style>

</head>
<body>

<h1><a href="https://www.citeit.net/">CiteIt.net</a></h1>
<h2>a higher standard of citation</h2>

<p>Submit your page to be indexed.  This will retreive the 500 characters of context before and after your quotation and store it in a JSON snippet.<p>

<form action="" method="POST">
  <input class="url" 
  	type="url" 
	name="url"
  	onfocus="if (this.value=='https://') this.value = 'https://'"
  	value="<?php print($_POST['url'] ? $_POST['url']: 'https://' ); ?>"
  >
  <input class="submit" type="submit" value="submit page" />
</form>

<!--p>This will generate JSON text snippets containing the 500 charactters of context for each quote.</p-->


<?php
if (isset($_POST['url'])){
  print("<h3>Results:</h3>");
  $json = get_json_from_webservice($_POST['url']);

}
?>

<div id="list_citations">
<?php
	print_json_files($JSON_FOLDER);
?>
</div>

<div id="footer">
	<p><b>What:</b> <a href="https://www.citeit.net/">CiteIt.net</a> is a citation tool that <b>enables web authors to demonstrate the context</b> of their citations.</p>
	<p><b>Who:</b> CiteIt.net allows journalists, academics and web authors who want to set a higher standard of discourse.</p>
	<p><b>How:</b> CiteIt.net is an <a href="https://www.citeit.net/code/">open source program</a> which can be added to a website with a WordPress plugin or a bit of custom code.</p>
</div>


<div id="navigation">
	<b>New Submission</b> | <a href="examples.php">Examples</a>
</div>


</body>
</html>