<!DOCTYPE html>
<html lang="en">
<head>
<title>API Reference</title>
<meta charset="utf-8">
</head>
<body>

    <h2>Users</h2>    
    <table>
        <tr>
            <th>Reference Code</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>100</td>
            <td>Name is a required field.</td>
        </tr>
        <tr>
            <td>101</td>
            <td>Duplicate name found.</td>
        </tr>
        <tr>
            <td>102</td>
            <td>Captcha failed.</td>
        </tr>
        <tr>
            <td>104</td>
            <td>User name must be 7 characters or longer.</td>
        </tr>
        <tr>
            <td>105</td>
            <td>Unable to load user, id not found.</td>
        </tr>
        <tr>
            <td>106</td>
            <td>Unable to load user, name not found.</td>
        </tr>
        <tr>
            <td>107</td>
            <td>Authentication failed.</td>
        </tr>
    </table>

    <h2>Decks</h2>
    <table>
        <tr>
            <th>Reference Code</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>200</td>
            <td>User ID not found.</td>
        </tr>
        <tr>
            <td>201</td>
            <td>Authentication failed.</td>
        </tr>
        <tr>
            <td>202</td>
            <td>Name is required.</td>
        </tr>
        <tr>
            <td>203</td>
            <td>Cards were found but could not be parsed.</td>
        </tr>
        <tr>
            <td>204</td>
            <td>Cards were parsed but contained some null or empty values.</td>
        </tr>
        <tr>
        <td>205</td>
            <td>Duplicate name found in this User.</td>
        </tr>
        <td>206</td>
            <td>Unable to load deck, user was not found.</td>
        </tr>
    </table>
    
    <h2>Cards</h2>
    <table>
        <tr>
            <th>Reference Code</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>300</td>
            <td>Deck ID not found.</td>
        </tr>
        <tr>
            <td>301</td>
            <td>Authentication failed.</td>
        </tr>
        <tr>
            <td>302</td>
            <td>Term is required.</td>
        </tr>
        <tr>
            <td>303</td>
            <td>Definition is required.</td>
        </tr>
    </table>

</body>
</html>
