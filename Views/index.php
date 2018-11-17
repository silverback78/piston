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
            <td>Username is a required field.</td>
        </tr>
        <tr>
            <td>101</td>
            <td>Duplicate username found.</td>
        </tr>
        <tr>
            <td>102</td>
            <td>Captcha failed.</td>
        </tr>
        <tr>
            <td>103</td>
            <td>Password is a required field.</td>
        </tr>
        <tr>
            <td>104</td>
            <td>Username must be 64 characters or less.</td>
        </tr>
        <tr>
            <td>105</td>
            <td>Unable to load user, username not found.</td>
        </tr>
        <tr>
            <td>107</td>
            <td>Authentication failed, email on file.</td>
        </tr>
        <tr>
            <td>108</td>
            <td>Authentication failed, no email on file.</td>
        </tr>
        <tr>
            <td>110</td>
            <td>Recovery code expired.</td>
        </tr>
        <tr>
            <td>112</td>
            <td>Error sending e-mail.</td>
        </tr>
        <tr>
            <td>113</td>
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
            <td>Deck name is required.</td>
        </tr>
        <tr>
            <td>201</td>
            <td>Deck was not found.</td>
        </tr>
        <tr>
            <td>202</td>
            <td>Duplicate deck name found.</td>
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
            <td>Invalid data structure for cards.</td>
        </tr>
        <tr>
            <td>301</td>
            <td>Term is required.</td>
        </tr>
        <tr>
            <td>302</td>
            <td>Definition is required.</td>
        </tr>
    </table>

</body>
</html>
