<html><body>
<b>Script log:</b><br>
<?php

# keys
$openai_api_key = "**************************************";
$openai_api_url = "https://api.openai.com/v1/chat/completions";

$mysql_host = "**************************************";
$mysql_port = 3306;
$mysql_username = "**************************************";
$mysql_password = "**************************************";
$mysql_database = "**************************************";


# step 1: create the world_currencies_table

// Establish database connection
$conn = new mysqli($mysql_host.":".$mysql_port, $mysql_username, $mysql_password, $mysql_database);
// Check connection
if ($conn->connect_error) {
  print("Database connection error<br>");
}
print("Database connected successfully<br>");


# delete the old table, if it exists
$sql = "DROP TABLE world_currencies";
if ($conn->query($sql) === TRUE) {
  print("Table world_currencies deleted successfully<br>");
} else {
  print( "Error deleting table: " . $conn->error."<br>");
}

# create the table
$sql = "CREATE TABLE world_currencies (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, currency_name VARCHAR(100) , country_name VARCHAR(100))";  # denomination VARCHAR(100), coin_or_bill VARCHAR(100), years_issued VARCHAR(100))";
if ($conn->query($sql) === TRUE) {
  print("Table world_currencies created successfully<br>");
} else {
  print("Error creating table: " . $conn->error."<br>");
}


# step 2: connect to ChatGPT and get a structured list of all the countries that existing in the last 150 years

// Initialize cURL session
$curl_connection=curl_init();

// Set up endpoint
curl_setopt($curl_connection,CURLOPT_URL, $openai_api_url);

// Return the response of the cURL command as a string
curl_setopt($curl_connection,CURLOPT_RETURNTRANSFER, true);

// Make sure this is submitted as POST
curl_setopt($curl_connection, CURLOPT_POST, 1);


$chat_gpt_data = array(
  "messages" => [["role" => "system", "content" => "For all the countries that existed at any time between 1800 and 2020, provide me information about their currencies. Provide the information in the following format: semicolon separated,  in the format: country_name / currency_name . Order the results in alphabeticla order."]],
  "temperature" => 0.5,
  "model" => "gpt-3.5-turbo",
  "max_tokens" => 2000
);
curl_setopt($curl_connection, CURLOPT_POSTFIELDS, json_encode($chat_gpt_data));

$chat_gpt_headers = array();
$chat_gpt_headers[] = 'Content-Type: application/json';
$chat_gpt_headers[] = 'Authorization: Bearer '.$openai_api_key;
curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $chat_gpt_headers);


// Execute the cURL call
$response = curl_exec($curl_connection);

// Close the cURL connection
curl_close($curl_connection);

// Parse the JSON string response into an array
$jsonArrayResponse = json_decode($response);

$list_of_countries = $jsonArrayResponse->{'choices'}[0]->{'message'}->{'content'};
$array_of_countries = explode(";",$list_of_countries);

# step 3: now that we have the list of countries, insert their currencies into the table

foreach ($array_of_countries as &$country_entry)
{
		$country_details = explode("/",$country_entry);
		$country_name = $country_details[0];
		$country_currency = $country_details[1];
				
		$sql = "INSERT INTO world_currencies (currency_name,country_name) VALUES ('".$country_details[1]."','".$country_details[0]."')"; 
		$conn->query($sql);
}

$conn->close();
?>
</body></html>